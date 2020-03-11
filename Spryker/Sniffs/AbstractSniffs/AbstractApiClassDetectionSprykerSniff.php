<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\AbstractSniffs;

use PHP_CodeSniffer\Files\File;

abstract class AbstractApiClassDetectionSprykerSniff extends AbstractSprykerSniff
{
    protected const API_FACADE = 'FACADE';
    protected const API_CLIENT = 'CLIENT';
    protected const API_QUERY_CONTAINER = 'QUERY_CONTAINER';
    protected const API_PLUGIN = 'PLUGIN';
    protected const API_CONFIG = 'CONFIG';

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return string|null
     */
    protected function sprykerApiClass(File $phpCsFile, int $stackPointer): ?string
    {
        if (!$this->hasNamespace($phpCsFile, $stackPointer) || !$this->hasClassOrInterfaceName($phpCsFile, $stackPointer)) {
            return null;
        }

        $namespace = $this->extractNamespace($phpCsFile, $stackPointer);
        $name = $this->getClassOrInterfaceName($phpCsFile, $stackPointer);

        if ($this->isFacade($namespace, $name)) {
            return static::API_FACADE;
        }
        if ($this->isClient($namespace, $name)) {
            return static::API_CLIENT;
        }
        if ($this->isQueryContainer($namespace, $name)) {
            return static::API_QUERY_CONTAINER;
        }
        if ($this->isPlugin($namespace, $name)) {
            return static::API_PLUGIN;
        }
        if ($this->isConfig($namespace, $name)) {
            return static::API_CONFIG;
        }

        return null;
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
    protected function extractNamespace(File $phpCsFile, int $stackPointer): string
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
     * @param string $namespace
     * @param string $name
     *
     * @return bool
     */
    protected function isConfig(string $namespace, string $name): bool
    {
        if (preg_match('/^Spryker[a-zA-Z]*\\\\[a-zA-Z]+\\\\[a-zA-Z]+$/', $namespace) && preg_match('/^\w+Config$/', $name)) {
            return true;
        }

        return false;
    }
}
