<?php

namespace Spryker\Sniffs\Whitespace;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Standards_AbstractVariableSniff;
use PHP_CodeSniffer_Tokens;

/**
 * Checks that the member var declarations have correct spacing.
 *
 * @author Mark Scherer
 * @license MIT
 */
class MemberVarSpacingSniff extends PHP_CodeSniffer_Standards_AbstractVariableSniff
{

    /**
     * @inheritdoc
     */
    protected function processMemberVar(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $ignore   = PHP_CodeSniffer_Tokens::$methodPrefixes;
        $ignore[] = T_VAR;
        $ignore[] = T_WHITESPACE;

        $endIndex = $phpcsFile->findNext(T_SEMICOLON, ($stackPtr + 1));
        $nextIndex = $phpcsFile->findNext(T_WHITESPACE, ($endIndex + 1), null, true);

        if ($tokens[$nextIndex]['line'] - $tokens[$endIndex]['line'] === 2) {
            return;
        }

        $found = $tokens[$nextIndex]['line'] - $tokens[$endIndex]['line'] - 1;
        $error = 'Expected 1 blank line after member var; %s found';
        $data = [$found];

        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'Incorrect', $data);

        if ($tokens[$nextIndex]['line'] - $tokens[$endIndex]['line'] < 2) {
            if ($fix === true) {
                $phpcsFile->fixer->addNewline($endIndex + 1);
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
    protected function processVariable(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        // We don't care about normal variables.
    }

    /**
     * @inheritdoc
     */
    protected function processVariableInString(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        // We don't care about normal variables.
    }

}
