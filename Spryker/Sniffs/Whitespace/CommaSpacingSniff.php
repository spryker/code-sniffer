<?php

/**
 * (c) Spryker Systems GmbH copyright protected.
 */
namespace Spryker\Sniffs\Whitespace;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Sniff;

/**
 * Ensures no whitespaces before and one whitespace after is placed around each comma.
 */
class CommaSpacingSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * @inheritdoc
     */
    public function register()
    {
        return [T_COMMA];
    }

    /**
     * @inheritdoc
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $next = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);

        if ($tokens[$next]['code'] !== T_WHITESPACE && ($next !== $stackPtr + 2)) {
            // Last character in a line is ok.
            if ($tokens[$next]['line'] === $tokens[$stackPtr]['line']) {
                $error = 'Missing space after comma';
                $phpcsFile->addError($error, $next);
            }
        }

        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);

        if ($tokens[$previous]['code'] !== T_WHITESPACE && ($previous !== $stackPtr - 1)) {
            if ($tokens[$previous]['code'] === T_COMMA) {
                return;
            }
            $error = 'Space before comma, expected none, though';
            $phpcsFile->addError($error, $next);
        }
    }

}
