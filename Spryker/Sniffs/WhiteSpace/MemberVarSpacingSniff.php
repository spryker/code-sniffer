<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\AbstractVariableSniff;

/**
 * Checks that the member var declarations have correct spacing.
 *
 * @author Mark Scherer
 * @license MIT
 */
class MemberVarSpacingSniff extends AbstractVariableSniff
{
    /**
     * @inheritDoc
     */
    protected function processMemberVar(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $endIndex = $phpcsFile->findNext(T_SEMICOLON, $stackPtr + 1);
        $nextIndex = $phpcsFile->findNext(T_WHITESPACE, $endIndex + 1, null, true);

        if ($tokens[$nextIndex]['line'] - $tokens[$endIndex]['line'] === 2) {
            return;
        }

        // If next token is end of class, we also skip
        if ($tokens[$nextIndex]['code'] === T_CLOSE_CURLY_BRACKET) {
            return;
        }

        $found = $tokens[$nextIndex]['line'] - $tokens[$endIndex]['line'] - 1;
        $error = 'Expected 1 blank line after member var; %s found';
        $data = [$found];

        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'Incorrect', $data);
        if (!$fix) {
            return;
        }

        if ($tokens[$nextIndex]['line'] - $tokens[$endIndex]['line'] < 2) {
            $phpcsFile->fixer->beginChangeset();
            $phpcsFile->fixer->addNewline($endIndex);
            $phpcsFile->fixer->endChangeset();

            return;
        }

        $phpcsFile->fixer->replaceToken($endIndex + 1, '');
    }

    /**
     * @inheritDoc
     */
    protected function processVariable(File $phpcsFile, $stackPtr)
    {
        // We don't care about normal variables.
    }

    /**
     * @inheritDoc
     */
    protected function processVariableInString(File $phpcsFile, $stackPtr)
    {
        // We don't care about normal variables.
    }
}
