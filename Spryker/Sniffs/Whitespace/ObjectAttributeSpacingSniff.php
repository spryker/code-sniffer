<?php

namespace Spryker\Sniffs\Whitespace;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Sniff;

/**
 * Verifies that that object operator and class double colon have no additional whitespace around.
 *
 * @author Mark Scherer
 * @license MIT
 */
class ObjectAttributeSpacingSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * @inheritDoc
     */
    public function register()
    {
        return [T_OBJECT_OPERATOR, T_DOUBLE_COLON];
    }

    /**
     * @inheritDoc
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Make sure there is no space before.
        $previousToken = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);

        if ($stackPtr - $previousToken !== 1 && $tokens[$previousToken]['line'] === $tokens[$stackPtr]['line']) {
            $error = 'Expected no space before object operator `' . $tokens[$stackPtr]['content'] . '`';
            $fix = $phpcsFile->addFixableError($error, $stackPtr - 1, 'TooMany');
            if ($fix) {
                $phpcsFile->fixer->replaceToken($stackPtr - 1, '');
            }
        }

        // Make sure there is no space after.
        $nextToken = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);

        if ($nextToken - $stackPtr !== 1 && $tokens[$nextToken]['line'] === $tokens[$stackPtr]['line']) {
            $error = 'Expected no space after object operator `' . $tokens[$stackPtr]['content'] . '`';
            $fix = $phpcsFile->addFixableError($error, $stackPtr + 1, 'TooMany');
            if ($fix) {
                $phpcsFile->fixer->replaceToken($stackPtr + 1, '');
            }
        }
    }

}
