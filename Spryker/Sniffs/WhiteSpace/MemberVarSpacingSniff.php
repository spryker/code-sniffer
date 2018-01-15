<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\AbstractVariableSniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks that the member var declarations have correct spacing.
 *
 * @author Mark Scherer
 * @license MIT
 */
class MemberVarSpacingSniff extends AbstractVariableSniff
{
    /**
     * @inheritdoc
     */
    protected function processMemberVar(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $ignore = Tokens::$methodPrefixes;
        $ignore[] = T_VAR;
        $ignore[] = T_WHITESPACE;

        $endIndex = $phpcsFile->findNext(T_SEMICOLON, ($stackPtr + 1));
        $nextIndex = $phpcsFile->findNext(T_WHITESPACE, ($endIndex + 1), null, true);

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

        if ($tokens[$nextIndex]['line'] - $tokens[$endIndex]['line'] < 2) {
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->addNewlineBefore($endIndex + 1);
                $phpcsFile->fixer->endChangeset();
            }

            return;
        }

        if ($fix === true) {
            $phpcsFile->fixer->replaceToken($endIndex + 1, '');
        }
    }

    /**
     * @inheritdoc
     */
    protected function processVariable(File $phpcsFile, $stackPtr)
    {
        // We don't care about normal variables.
    }

    /**
     * @inheritdoc
     */
    protected function processVariableInString(File $phpcsFile, $stackPtr)
    {
        // We don't care about normal variables.
    }
}
