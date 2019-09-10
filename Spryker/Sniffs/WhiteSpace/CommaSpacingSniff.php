<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Ensures no whitespaces before and one whitespace after is placed around each comma.
 *
 * @author Mark Scherer
 * @license MIT
 */
class CommaSpacingSniff implements Sniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_COMMA];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $next = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        $this->checkNext($phpcsFile, $stackPtr, $next);

        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);

        if ($tokens[$previous]['code'] !== T_WHITESPACE && ($previous !== $stackPtr - 1)) {
            if ($tokens[$previous]['code'] === T_COMMA) {
                return;
            }

            $error = 'Space before comma, expected none, though';
            $fix = $phpcsFile->addFixableError($error, $next, 'InvalidCommaBefore');
            if ($fix) {
                $phpcsFile->fixer->replaceToken($previous + 1, '');
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     * @param int $next
     *
     * @return void
     */
    public function checkNext(File $phpcsFile, int $stackPtr, int $next): void
    {
        $tokens = $phpcsFile->getTokens();

        // Closing inline array should not have a comma before
        if ($tokens[$next]['code'] === T_CLOSE_SHORT_ARRAY && $tokens[$next]['line'] === $tokens[$stackPtr]['line']) {
            $error = 'Invalid comma before closing inline array end `]`.';
            $fix = $phpcsFile->addFixableError($error, $next, 'InvalidCommaInline');
            if ($fix) {
                $phpcsFile->fixer->replaceToken($stackPtr, '');
            }

            return;
        }

        if ($tokens[$next]['code'] !== T_WHITESPACE && ($next !== $stackPtr + 2)) {
            // Last character in a line is ok.
            if ($tokens[$next]['line'] !== $tokens[$stackPtr]['line']) {
                return;
            }

            // Closing inline array is also ignored
            if ($tokens[$next]['code'] === T_CLOSE_SHORT_ARRAY) {
                return;
            }

            $error = 'Missing space after comma';
            $fix = $phpcsFile->addFixableError($error, $next, 'MissingCommaAfter');
            if ($fix) {
                $phpcsFile->fixer->addContent($stackPtr, ' ');
            }
        }
    }
}
