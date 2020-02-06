<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Checks if doc block of Spryker API classes (Client, Facade, QueryContainer, Plugin) contain `@api` annotations.
 */
class DocBlockApiAnnotationSniff implements Sniff
{
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
        if (!$this->isSprykerApiClass($phpCsFile, $stackPointer) || !$this->isPublicMethod($phpCsFile, $stackPointer)) {
            // To be finalized once all plugins are detected properly.
            //$this->assertNoApiTag($phpCsFile, $stackPointer);

            return;
        }

        $this->assertApiAnnotation($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isSprykerApiClass(File $phpCsFile, int $stackPointer): bool
    {
        if (!$this->hasNamespace($phpCsFile, $stackPointer) || !$this->hasClassOrInterfaceName($phpCsFile, $stackPointer)) {
            return false;
        }

        $namespace = $this->getNamespace($phpCsFile, $stackPointer);
        $name = $this->getClassOrInterfaceName($phpCsFile, $stackPointer);

        if (
            $this->isFacade($namespace, $name)
            || $this->isClient($namespace, $name)
            || $this->isQueryContainer($namespace, $name)
            || $this->isPlugin($namespace, $name)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isPublicMethod(File $phpCsFile, int $stackPointer): bool
    {
        $publicPosition = $phpCsFile->findFirstOnLine(T_PUBLIC, $stackPointer);
        if ($publicPosition) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasNamespace(File $phpCsFile, int $stackPointer): bool
    {
        $namespacePosition = $phpCsFile->findPrevious(T_NAMESPACE, $stackPointer);
        if (!$namespacePosition) {
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
    protected function hasClassOrInterfaceName(File $phpCsFile, int $stackPointer): bool
    {
        $classOrInterfaceNamePosition = $phpCsFile->findPrevious([T_CLASS, T_INTERFACE], $stackPointer);
        if (!$classOrInterfaceNamePosition) {
            return false;
        }

        return true;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return string
     */
    protected function getNamespace(File $phpCsFile, int $stackPointer): string
    {
        $namespacePosition = $phpCsFile->findPrevious(T_NAMESPACE, $stackPointer);
        $endOfNamespacePosition = $phpCsFile->findEndOfStatement($namespacePosition);

        $tokens = $phpCsFile->getTokens();
        $namespaceTokens = array_splice($tokens, $namespacePosition + 2, $endOfNamespacePosition - $namespacePosition - 2);

        $namespace = '';
        foreach ($namespaceTokens as $token) {
            $namespace .= $token['content'];
        }

        return $namespace;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return string
     */
    protected function getClassOrInterfaceName(File $phpCsFile, int $stackPointer): string
    {
        $classOrInterfacePosition = $phpCsFile->findPrevious([T_CLASS, T_INTERFACE], $stackPointer);
        $classOrInterfaceNamePosition = $phpCsFile->findNext(T_STRING, $classOrInterfacePosition);

        return $phpCsFile->getTokens()[$classOrInterfaceNamePosition]['content'];
    }

    /**
     * @param string $namespace
     * @param string $name
     *
     * @return bool
     */
    protected function isFacade(string $namespace, string $name): bool
    {
        if (preg_match('/^Spryker[a-zA-Z]*\\\\Zed\\\\[a-zA-Z]+\\\\Business$/', $namespace) && preg_match('/^(.*?)(Facade|FacadeInterface)$/', $name)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $namespace
     * @param string $name
     *
     * @return bool
     */
    protected function isClient(string $namespace, string $name): bool
    {
        if (preg_match('/^Spryker[a-zA-Z]*\\\\Client\\\\[a-zA-Z]+$/', $namespace) && preg_match('/^(.*?)(Client|ClientInterface)$/', $name)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $namespace
     * @param string $name
     *
     * @return bool
     */
    protected function isPlugin(string $namespace, string $name): bool
    {
        if (preg_match('/^Spryker[a-zA-Z]*\\\\[a-zA-Z]+\\\\[a-zA-Z]+\\\\Dependency\\\\Plugin\b/', $namespace) && preg_match('/^\w+Interface$/', $name)) {
            return true;
        }

        if (preg_match('/^Spryker[a-zA-Z]*\\\\[a-zA-Z]+\\\\[a-zA-Z]+\\\\Communication\\\\Plugin\b/', $namespace) && preg_match('/^\w+Plugin$/', $name)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $namespace
     * @param string $name
     *
     * @return bool
     */
    protected function isQueryContainer(string $namespace, string $name): bool
    {
        if (preg_match('/^Spryker[a-zA-Z]*\\\\Zed\\\(.*?)\\\\Persistence$/', $namespace) && preg_match('/^(.*?)(QueryContainer|QueryContainerInterface)$/', $name)) {
            return true;
        }

        return false;
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

        $docCommentClosingPosition = $phpCsFile->findNext(T_DOC_COMMENT_CLOSE_TAG, $docCommentOpenerPosition);

        $tokens = $phpCsFile->getTokens();

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
     *
     * @return void
     */
    protected function assertApiAnnotation(File $phpCsFile, int $stackPointer): void
    {
        if ($this->findApiAnnotationIndex($phpCsFile, $stackPointer)) {
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
}
