<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Checks that the namespace declaration and body has correct spacing.
 *
 * @author Mark Scherer
 * @license MIT
 */
class NamespaceSpacingSniff extends AbstractSprykerSniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_NAMESPACE];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $beforeIndex = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        $beforeLine = $tokens[$beforeIndex]['line'];

        if ($beforeLine === $tokens[$stackPtr]['line'] - 2) {
            return;
        }

        $error = 'There must be one blank line before the namespace declaration';
        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'BlankLineBefore');
        if (!$fix) {
            return;
        }

        if ($beforeLine < $tokens[$stackPtr]['line'] - 2) {
            $phpcsFile->fixer->beginChangeset();
            $phpcsFile->fixer->replaceToken($stackPtr - 1, '');
            $phpcsFile->fixer->endChangeset();

            return;
        }

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->addNewline($beforeIndex);
        $phpcsFile->fixer->endChangeset();
    }
}
