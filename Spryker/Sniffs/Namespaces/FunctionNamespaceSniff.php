<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Do not use namespaced function usage.
 */
class FunctionNamespaceSniff implements Sniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_STRING];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $tokenContent = $tokens[$stackPtr]['content'];

        $openingBrace = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if (!$openingBrace || $tokens[$openingBrace]['type'] !== 'T_OPEN_PARENTHESIS') {
            return;
        }

        $separatorIndex = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if (!$separatorIndex || $tokens[$separatorIndex]['type'] !== 'T_NS_SEPARATOR') {
            return;
        }

        // We check that this is a function but not new operator
        $newIndex = $phpcsFile->findPrevious([T_WHITESPACE, T_NS_SEPARATOR], ($stackPtr - 1), null, true);
        if (!$newIndex || $tokens[$newIndex]['type'] === 'T_NEW') {
            return;
        }

        // We skip for non trivial cases
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($separatorIndex - 1), null, true);
        if (!$previous || $tokens[$previous]['type'] === 'T_STRING') {
            return;
        }

        $error = 'Function name ' . $tokenContent . '() found, should not be \ prefixed.';
        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'NamespaceInvalid');
        if ($fix) {
            $phpcsFile->fixer->replaceToken($separatorIndex, '');
        }
    }
}
