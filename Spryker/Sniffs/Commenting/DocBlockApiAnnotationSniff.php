<?php

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Sniff;

/**
 * Checks if doc block of Spryker API classes (Client, Facade and QueryContainer) contain @api annotations
 */
class DocBlockApiAnnotationSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * @return array
     */
    public function register()
    {
        return [
            T_FUNCTION,
        ];
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        if (!$this->isSprykerApiClass($phpCsFile, $stackPointer) || !$this->isPublicMethod($phpCsFile, $stackPointer)) {
            return;
        }

        if (!$this->hasApiAnnotation($phpCsFile, $stackPointer)) {
            $this->addFixableMissingApiAnnotation($phpCsFile, $stackPointer);
        }
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isSprykerApiClass(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        if (!$this->hasNamespace($phpCsFile, $stackPointer) || !$this->hasClassOrInterfaceName($phpCsFile, $stackPointer)) {
            return false;
        }

        $namespace = $this->getNamespace($phpCsFile, $stackPointer);
        $name = $this->getClassOrInterfaceName($phpCsFile, $stackPointer);

        if ($this->isFacade($namespace, $name)
            || $this->isClient($namespace, $name)
            || $this->isQueryContainer($namespace, $name)
            || $this->isPluginInterface($namespace, $name)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isPublicMethod(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $publicPosition = $phpCsFile->findFirstOnLine(T_PUBLIC, $stackPointer);
        if ($publicPosition) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasNamespace(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $namespacePosition = $phpCsFile->findPrevious(T_NAMESPACE, $stackPointer);
        if (!$namespacePosition) {
            return false;
        }

        return true;
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasClassOrInterfaceName(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $classOrInterfaceNamePosition = $phpCsFile->findPrevious([T_CLASS, T_INTERFACE], $stackPointer);
        if (!$classOrInterfaceNamePosition) {
            return false;
        }

        return true;
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return string
     */
    protected function getNamespace(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
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
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return string
     */
    protected function getClassOrInterfaceName(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
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
    protected function isFacade($namespace, $name)
    {
        if (preg_match('/^Spryker\\\Zed\\\(.*?)\\\Business$/', $namespace) && preg_match('/^(.*?)(Facade|FacadeInterface)/', $name)) {
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
    protected function isClient($namespace, $name)
    {
        if (preg_match('/^Spryker\\\Client\\\[a-zA-Z]+$/', $namespace) && preg_match('/^(.*?)(Client|ClientInterface)/', $name)) {
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
    protected function isPluginInterface($namespace, $name)
    {
        if (preg_match('/^Spryker\\\\[a-zA-Z]+\\\\[a-zA-Z]+\\\\Dependency\\\\Plugin\b/', $namespace) && preg_match('/^\w+Interface$/', $name)) {
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
    protected function isQueryContainer($namespace, $name)
    {
        if (preg_match('/^Spryker\\\Zed\\\(.*?)\\\Persistence$/', $namespace) && preg_match('/^(.*?)(QueryContainer|QueryContainerInterface)/', $name)) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasApiAnnotation(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $docCommentOpenerPosition = $phpCsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $stackPointer);
        if (!$docCommentOpenerPosition) {
            return true;
        }

        $docCommentClosingPosition = $phpCsFile->findNext(T_DOC_COMMENT_CLOSE_TAG, $docCommentOpenerPosition);

        $tokens = $phpCsFile->getTokens();
        $docCommentTokens = array_slice($tokens, $docCommentOpenerPosition, $docCommentClosingPosition - $docCommentOpenerPosition);

        foreach ($docCommentTokens as $docCommentToken) {
            if ($docCommentToken['type'] === 'T_DOC_COMMENT_TAG' && $docCommentToken['content'] === '@api') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function addFixableMissingApiAnnotation(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $fix = $phpCsFile->addFixableError('@api annotation is missing', $stackPointer);

        if ($fix) {
            $docCommentOpenerPosition = $phpCsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $stackPointer);
            $firstDocCommentTagPosition = $phpCsFile->findNext(T_DOC_COMMENT_TAG, $docCommentOpenerPosition);

            if (!$firstDocCommentTagPosition) {
                $phpCsFile->addErrorOnLine('Cannot fix missing @api tag', $stackPointer);

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
    }

}
