<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Traits;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

trait BasicsTrait
{
    /**
     * @param string|int|array $search
     * @param array $token
     *
     * @return bool
     */
    protected function isGivenKind($search, array $token): bool
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
    protected function getNamespaceStatement(File $phpcsFile): array
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
