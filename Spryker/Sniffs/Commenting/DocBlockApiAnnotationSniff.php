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

    protected const SPECIFICATION_TAG = 'Specification';

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
        $this->assertSpecification($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return int|null
     */
    protected function findApiAnnotationIndex(File $phpCsFile, int $stackPointer): ?int
    {
        $docCommentOpenerPosition = $this->getDocOpenerPosition($phpCsFile, $stackPointer);
        if (!$docCommentOpenerPosition) {
            return null;
        }

        $tokens = $phpCsFile->getTokens();
        $docCommentClosingPosition = $this->getDocClosingPosition($phpCsFile, $stackPointer);
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

        $docCommentOpenerPosition = $this->getDocOpenerPosition($phpCsFile, $stackPointer);
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

        $docCommentOpenerPosition = $this->getDocOpenerPosition($phpCsFile, $stackPointer);
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
            $phpCsFile->addError(
                sprintf(
                    '`%s` is only for concrete classes, the interfaces should contain the specification themselves.',
                    static::INHERIT_DOC
                ),
                $i,
                'InvalidInheritDoc'
            );

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
     * Asserts that "Specification:" is used for interface, Plugin or Config, and must not be used for concrete class.
     *
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function assertSpecification(File $phpCsFile, int $stackPointer): void
    {
        $docCommentOpenerPosition = $this->getDocOpenerPosition($phpCsFile, $stackPointer);
        if (!$docCommentOpenerPosition) {
            return;
        }
        $docCommentClosingPosition = $this->getDocClosingPosition($phpCsFile, $stackPointer);
        if (!$docCommentClosingPosition) {
            return;
        }

        if ($this->specificationAllowedClass($phpCsFile, $stackPointer)) {
            $this->assertSpecificationAllowed($phpCsFile, $stackPointer);

            return;
        }

        if ($this->specificationRequiredClass($phpCsFile, $stackPointer)) {
            $this->assertSpecificationRequired($phpCsFile, $stackPointer);

            return;
        }

        if ($this->specificationForbiddenClass($phpCsFile, $stackPointer)) {
            $this->assertSpecificationForbidden($phpCsFile, $stackPointer);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    public function assertSpecificationAllowed(File $phpCsFile, int $stackPointer): void
    {
        $this->validateSpecification($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    public function assertSpecificationRequired(File $phpCsFile, int $stackPointer): void
    {
        $tokens = $phpCsFile->getTokens();
        $specificationPresent = $this->validateSpecification($phpCsFile, $stackPointer);
        if ($specificationPresent) {
            return;
        }
        $phpCsFile->addErrorOnLine(
            'Cannot fix missing specification for API',
            $tokens[$stackPointer]['line'],
            'SpecificationNotFixable'
        );
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return int|null
     */
    public function validateSpecification(File $phpCsFile, int $stackPointer): ?int
    {
        $tokens = $phpCsFile->getTokens();
        $docCommentOpenerPosition = $this->getDocOpenerPosition($phpCsFile, $stackPointer);
        $docCommentClosingPosition = $this->getDocClosingPosition($phpCsFile, $stackPointer);

        $specificationPosition = $this->getContentPositionInRange(
            static::SPECIFICATION_TAG,
            $tokens,
            $docCommentOpenerPosition,
            $docCommentClosingPosition
        );
        if (!$specificationPosition) {
            return null;
        }
        $this->assertSpecificationFormat($phpCsFile, $specificationPosition);

        return $specificationPosition;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    public function assertSpecificationFormat(File $phpCsFile, int $stackPointer): void
    {
        $tokens = $phpCsFile->getTokens();
        $tokenContent = $tokens[$stackPointer]['content'];
        if ($tokenContent === sprintf('%s:', static::SPECIFICATION_TAG)) {
            return;
        }
        $this->addTypoInSpecificationTagFixableError($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    public function addTypoInSpecificationTagFixableError(File $phpCsFile, int $stackPointer): void
    {
        $tokenContent = $phpCsFile->getTokens()[$stackPointer]['content'];
        $fix = $phpCsFile->addFixableError('Typo in Specification tag.', $stackPointer, 'SpecificationTypo');
        if ($fix) {
            $phpCsFile->fixer->beginChangeset();
            $phpCsFile->fixer->replaceToken($stackPointer, sprintf('%s:', static::SPECIFICATION_TAG));
            $phpCsFile->fixer->endChangeset();
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $line
     * @param int $stackPointer
     *
     * @return void
     */
    public function addWrongSpecificationTagIndentationFixableError(File $phpCsFile, int $line, int $stackPointer): void
    {
        $fix = $phpCsFile->addFixableError(
            'Wrong indentation in specification block',
            $line,
            'SpecificationItemIndentation'
        );
        if ($fix) {
            $phpCsFile->fixer->beginChangeset();
            $phpCsFile->fixer->replaceToken($stackPointer, ' ');
            $phpCsFile->fixer->endChangeset();
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    public function assertSpecificationForbidden(File $phpCsFile, int $stackPointer): void
    {
        $tokens = $phpCsFile->getTokens();
        $specificationPosition = $this->validateSpecification($phpCsFile, $stackPointer);
        if ($specificationPosition !== null) {
            $phpCsFile->addErrorOnLine(
                'Specification is not allowed in this type of class',
                $tokens[$specificationPosition]['line'],
                'SpecificationNotFixable'
            );
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    public function specificationRequiredClass(File $phpCsFile, int $stackPointer): bool
    {
        return $this->isInterface($phpCsFile, $stackPointer) && $this->sprykerApiClass($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    public function specificationAllowedClass(File $phpCsFile, int $stackPointer): bool
    {
        $namespace = $this->extractNamespace($phpCsFile, $stackPointer);
        $name = $this->getClassOrInterfaceName($phpCsFile, $stackPointer);

        return $this->isConfig($namespace, $name) ||
            $this->isPlugin($namespace, $name);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    public function specificationForbiddenClass(File $phpCsFile, int $stackPointer): bool
    {
        $namespace = $this->extractNamespace($phpCsFile, $stackPointer);
        $name = $this->getClassOrInterfaceName($phpCsFile, $stackPointer);

        return !$this->isInterface($phpCsFile, $stackPointer) &&
            !$this->isConfig($namespace, $name) &&
            !$this->isPlugin($namespace, $name);
    }

    /**
     * @param string $content
     * @param array $tokens
     * @param int $beginRange
     * @param int $endRange
     *
     * @return int|null
     */
    protected function getContentPositionInRange(
        string $content,
        array $tokens,
        int $beginRange = 0,
        int $endRange = 0
    ): ?int {
        for ($i = $beginRange + 1; $i < $endRange; $i++) {
            if (stripos($tokens[$i]['content'], $content) === false) {
                continue;
            }

            return $i;
        }

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return int|null
     */
    protected function getDocOpenerPosition(File $phpCsFile, int $stackPointer): ?int
    {
        return $phpCsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $stackPointer) ?
            $phpCsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $stackPointer) :
            null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return int|null
     */
    protected function getDocClosingPosition(File $phpCsFile, int $stackPointer): ?int
    {
        $docCommentOpenerPosition = $this->getDocOpenerPosition($phpCsFile, $stackPointer);
        if (!$docCommentOpenerPosition) {
            return null;
        }
        $tokens = $phpCsFile->getTokens();

        return $tokens[$docCommentOpenerPosition]['comment_closer'] ?
            $tokens[$docCommentOpenerPosition]['comment_closer'] :
            null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function assertInheritDocTag(File $phpCsFile, int $stackPointer): void
    {
        $docCommentOpenerPosition = $this->getDocOpenerPosition($phpCsFile, $stackPointer);
        if (!$docCommentOpenerPosition) {
            return;
        }

        $docCommentClosingPosition = $this->getDocClosingPosition($phpCsFile, $stackPointer);
        if (!$docCommentClosingPosition) {
            return;
        }

        $tokens = $phpCsFile->getTokens();
        $hasInheritDoc = (bool)$this->getContentPositionInRange(
            '@inheritDoc',
            $tokens,
            $docCommentOpenerPosition,
            $docCommentClosingPosition
        );

        if ($hasInheritDoc) {
            return;
        }

        $fix = $phpCsFile->addFixableError(
            sprintf(
                '`%s` missing for API method.',
                static::INHERIT_DOC
            ),
            $docCommentOpenerPosition,
            'InheritDocMissing'
        );
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
