<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Traits;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

trait BasicsTrait
{
    /**
     * @param string|array $search
     * @param array $token
     *
     * @return bool
     */
    protected function isGivenKind($search, array $token)
    {
        $kind = (array)$search;

        if (in_array($token['code'], $kind, true)) {
            return true;
        }
        if (in_array($token['type'], $kind, true)) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     *
     * @return array
     */
    protected function getNamespaceStatement(File $phpcsFile)
    {
        $tokens = $phpcsFile->getTokens();

        $namespaceIndex = $phpcsFile->findNext(T_NAMESPACE, 0);
        if (!$namespaceIndex) {
            return [];
        }

        $endIndex = $phpcsFile->findNext([T_SEMICOLON, T_OPEN_CURLY_BRACKET], $namespaceIndex + 1);
        if (!$endIndex) {
            return [];
        }

        $namespace = '';
        $namespaceStartIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $namespaceIndex + 1, null, true);
        $namespaceEndIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $endIndex - 1, null, true);
        for ($i = $namespaceStartIndex; $i <= $namespaceEndIndex; $i++) {
            $namespace .= $tokens[$i]['content'];
        }

        return [
            'start' => $namespaceIndex,
            'namespace' => $namespace,
            'end' => $endIndex,
        ];
    }
}
