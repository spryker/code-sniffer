<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractApiClassDetectionSprykerSniff;

/**
 * Checks if doc block of Spryker API classes (Client, Facade, QueryContainer, Plugin) contain `@api` annotations.
 */
class DocBlockApiAnnotationSniff extends AbstractApiClassDetectionSprykerSniff
{
    protected const INHERIT_DOC = '{@inheritDoc}';

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_FUNCTION,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        $apiClass = $this->sprykerApiClass($phpCsFile, $stackPointer);

        if ($apiClass === null || !$this->isPublicMethod($phpCsFile, $stackPointer) || $this->isConstructor($phpCsFile, $stackPointer)) {
            // To be finalized once all plugins are detected properly.
            //$this->assertNoApiTag($phpCsFile, $stackPointer);

            return;
        }

        $this->assertApiAnnotation($phpCsFile, $stackPointer, $apiClass);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return int|null
     */
    protected function findApiAnnotationIndex(File $phpCsFile, int $stackPointer): ?int
    {
        $docCommentOpenerPosition = $phpCsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $stackPointer);
        if (!$docCommentOpenerPosition) {
            return null;
        }

        $tokens = $phpCsFile->getTokens();
        $docCommentClosingPosition = $tokens[$docCommentOpenerPosition]['comment_closer'];
        if (!$docCommentClosingPosition) {
            return null;
        }

        for ($i = $docCommentOpenerPosition + 1; $i < $docCommentClosingPosition; $i++) {
            $docCommentToken = $tokens[$i];
            if ($docCommentToken['type'] === 'T_DOC_COMMENT_TAG' && $docCommentToken['content'] === '@api') {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     * @param string|null $apiClass
     *
     * @return void
     */
    protected function assertApiAnnotation(File $phpCsFile, int $stackPointer, ?string $apiClass): void
    {
        $apiAnnotationIndex = $this->findApiAnnotationIndex($phpCsFile, $stackPointer);
        if ($apiAnnotationIndex) {
            if ($apiClass !== static::API_CONFIG) {
                $this->assertInheritDoc($phpCsFile, $stackPointer);
            }

            return;
        }

        $fix = $phpCsFile->addFixableError('@api annotation is missing', $stackPointer, 'ApiAnnotationMissing');
        if (!$fix) {
            return;
        }

        $docCommentOpenerPosition = $phpCsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $stackPointer);
        $firstDocCommentTagPosition = $phpCsFile->findNext(T_DOC_COMMENT_TAG, $docCommentOpenerPosition);

        if (!$firstDocCommentTagPosition) {
            $phpCsFile->addErrorOnLine('Cannot fix missing @api tag', $stackPointer, 'ApiAnnotationNotFixable');

            return;
        }

        $startPosition = $firstDocCommentTagPosition - 2;
        $phpCsFile->fixer->beginChangeset();
        $phpCsFile->fixer->addContent($startPosition, ' @api');
        $phpCsFile->fixer->addNewline($startPosition);
        $phpCsFile->fixer->addContent($startPosition, ' * ');
        $phpCsFile->fixer->addNewline($startPosition);
        $phpCsFile->fixer->addContent($startPosition, '    * ');
        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function assertNoApiTag(File $phpCsFile, int $stackPointer): void
    {
        $apiIndex = $this->findApiAnnotationIndex($phpCsFile, $stackPointer);
        if (!$apiIndex) {
            return;
        }

        $fix = $phpCsFile->addFixableError('@api annotation is invalid.', $stackPointer, 'ApiAnnotationInvalid');

        if (!$fix) {
            return;
        }

        $tokens = $phpCsFile->getTokens();
        $line = $tokens[$apiIndex]['line'];

        $phpCsFile->fixer->beginChangeset();
        $phpCsFile->fixer->replaceToken($apiIndex, '');

        $index = $apiIndex;
        while ($tokens[$index - 1]['line'] === $line) {
            $index--;
            $phpCsFile->fixer->replaceToken($index, '');
        }
        $index = $apiIndex;
        while ($tokens[$index + 1]['line'] === $line) {
            $index++;
            $phpCsFile->fixer->replaceToken($index, '');
        }

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * Asserts that {@inheritDoc} is used for concrete class, and must not be used for interface.
     *
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function assertInheritDoc(File $phpCsFile, int $stackPointer): void
    {
        if (!$this->isInterface($phpCsFile, $stackPointer)) {
            $this->assertInheritDocTag($phpCsFile, $stackPointer);

            return;
        }

        $docCommentOpenerPosition = $phpCsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $stackPointer);
        if (!$docCommentOpenerPosition) {
            return;
        }

        $tokens = $phpCsFile->getTokens();
        $docCommentClosingPosition = $tokens[$docCommentOpenerPosition]['comment_closer'];
        if (!$docCommentClosingPosition) {
            return;
        }

        for ($i = $docCommentOpenerPosition + 1; $i < $docCommentClosingPosition; $i++) {
            if (strpos($tokens[$i]['content'], '@inheritDoc') === false) {
                continue;
            }

            $phpCsFile->addError(static::INHERIT_DOC . ' is only for concrete classes, the interfaces should contain the specification themselves.', $i, 'InvalidInheritDoc');

            break;
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isInterface(File $phpCsFile, int $stackPointer): bool
    {
        $interfaceIndex = $phpCsFile->findPrevious(T_INTERFACE, $stackPointer);
        if (!$interfaceIndex) {
            return false;
        }

        return true;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isConstructor(File $phpCsFile, int $stackPointer): bool
    {
        $methodNameIndex = $phpCsFile->findNext(T_STRING, $stackPointer);

        $tokens = $phpCsFile->getTokens();

        return $tokens[$methodNameIndex]['content'] === '__construct';
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function assertInheritDocTag(File $phpCsFile, int $stackPointer): void
    {
        $docCommentOpenerPosition = $phpCsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $stackPointer);
        if (!$docCommentOpenerPosition) {
            return;
        }

        $tokens = $phpCsFile->getTokens();
        $docCommentClosingPosition = $tokens[$docCommentOpenerPosition]['comment_closer'];
        if (!$docCommentClosingPosition) {
            return;
        }

        $hasInheritDoc = false;
        for ($i = $docCommentOpenerPosition + 1; $i < $docCommentClosingPosition; $i++) {
            if (stripos($tokens[$i]['content'], '@inheritDoc') === false) {
                continue;
            }

            $hasInheritDoc = true;

            break;
        }

        if ($hasInheritDoc) {
            return;
        }

        $fix = $phpCsFile->addFixableError(static::INHERIT_DOC . ' missing for API method.', $docCommentOpenerPosition, 'InheritDocMissing');
        if (!$fix) {
            return;
        }

        $lastTokenOnLine = $this->lastTokenOnLine($tokens, $docCommentOpenerPosition);
        $firstTokenOnNextLine = $this->firstTokenOnLine($tokens, $lastTokenOnLine + 1);

        if (!$firstTokenOnNextLine) {
            $phpCsFile->addErrorOnLine('Cannot fix missing inheritDoc tag', $stackPointer, 'InheritDocNotFixable');

            return;
        }

        $phpCsFile->fixer->beginChangeset();

        $phpCsFile->fixer->addContent($firstTokenOnNextLine, str_repeat(' ', 4) . ' * ' . static::INHERIT_DOC);
        $phpCsFile->fixer->addNewline($firstTokenOnNextLine);

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param array $tokens
     * @param int $currentIndex
     *
     * @return int
     */
    protected function lastTokenOnLine(array $tokens, int $currentIndex): int
    {
        $line = $tokens[$currentIndex]['line'];
        while ($tokens[$currentIndex + 1]['line'] === $line) {
            $currentIndex++;
        }

        return $currentIndex;
    }

    /**
     * @param array $tokens
     * @param int $currentIndex
     *
     * @return int
     */
    protected function firstTokenOnLine(array $tokens, int $currentIndex): int
    {
        $line = $tokens[$currentIndex]['line'];
        while ($tokens[$currentIndex - 1]['line'] === $line) {
            $currentIndex--;
        }

        return $currentIndex;
    }
}
