<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Usage of `else if` is not allowed, use `elseif` instead.
 */
class ElseIfDeclarationSniff implements Sniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_ELSE,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $next = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if ($tokens[$next]['code'] === T_IF) {
            $phpcsFile->recordMetric($stackPtr, 'Use of ELSE IF or ELSEIF', 'else if');
            $error = 'Usage of `else if` is not allowed, use `elseif` instead';
            $fix = $phpcsFile->addFixableError($error, $stackPtr, 'NotAllowed');

            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->replaceToken($stackPtr, 'elseif');
                for ($i = ($stackPtr + 1); $i <= $next; $i++) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }

                $phpcsFile->fixer->endChangeset();
            }
        }
    }
}
