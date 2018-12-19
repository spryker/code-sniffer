<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Traits;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

trait BridgeTrait
{
    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $pointer
     *
     * @return bool
     */
    protected function isSprykerBridgeConstructor(File $phpCsFile, int $pointer): bool
    {
        $tokens = $phpCsFile->getTokens();

        $token = $tokens[$pointer];
        if ($token['code'] !== T_FUNCTION) {
            return false;
        }

        $nextIndex = $phpCsFile->findNext(Tokens::$emptyTokens, $pointer + 1, null, true);
        if (!$nextIndex) {
            return false;
        }

        $name = $tokens[$nextIndex]['content'];
        if ($name !== '__construct') {
            return false;
        }

        $classPointer = $phpCsFile->findPrevious([T_CLASS, T_INTERFACE], $pointer - 1);
        if (!$classPointer || !$this->isSprykerBridge($phpCsFile, $classPointer)) {
            return false;
        }

        return true;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $pointer
     *
     * @return bool
     */
    protected function isSprykerBridge(File $phpCsFile, int $pointer): bool
    {
        if (!$this->hasNamespace($phpCsFile, $pointer)) {
            return false;
        }

        $namespace = $this->getNamespace($phpCsFile, $pointer);
        if (!preg_match('/^Spryker.*\\\\/', $namespace)) {
            return false;
        }

        $name = $this->findClassOrInterfaceName($phpCsFile, $pointer);
        if (!$name || substr($name, -6) !== 'Bridge') {
            return false;
        }

        return true;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $pointer
     *
     * @return bool
     */
    protected function hasNamespace(File $phpCsFile, int $pointer): bool
    {
        $namespacePosition = $phpCsFile->findPrevious(T_NAMESPACE, $pointer);
        if (!$namespacePosition) {
            return false;
        }

        return true;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $pointer
     *
     * @return string
     */
    protected function getNamespace(File $phpCsFile, int $pointer): string
    {
        $namespacePosition = $phpCsFile->findPrevious(T_NAMESPACE, $pointer);
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
     * @param int $pointer
     *
     * @return string
     */
    protected function findClassOrInterfaceName(File $phpCsFile, int $pointer): string
    {
        $classOrInterfaceNamePosition = $phpCsFile->findNext(T_STRING, $pointer);

        return $phpCsFile->getTokens()[$classOrInterfaceNamePosition]['content'];
    }
}
