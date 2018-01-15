<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * There should always be newlines around functions/methods.
 */
class FunctionSpacingSniff implements Sniff
{
    /**
     * @inheritdoc
     */
    public function register()
    {
        return [T_FUNCTION];
    }

    /**
     * @inheritdoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        $tokens = $phpCsFile->getTokens();

        $level = $tokens[$stackPointer]['level'];
        if ($level < 1) {
            return;
        }

        $openingBraceIndex = $phpCsFile->findNext(T_OPEN_CURLY_BRACKET, $stackPointer + 1);
        if (!$openingBraceIndex) {
            $openingParenthesisIndex = $phpCsFile->findNext(T_OPEN_PARENTHESIS, $stackPointer + 1);
            $closingParenthesisIndex = $tokens[$openingParenthesisIndex]['parenthesis_closer'];

            $php7Tokens = [T_COLON => T_COLON, T_WHITESPACE => T_WHITESPACE, T_RETURN_TYPE => T_RETURN_TYPE, T_NULLABLE => T_NULLABLE];
            $skippedTokens = array_merge(Tokens::$emptyTokens, $php7Tokens);
            $semicolonIndex = $phpCsFile->findNext($skippedTokens, $closingParenthesisIndex + 1, null, true);

            $nextContentIndex = $phpCsFile->findNext(Tokens::$emptyTokens, $semicolonIndex + 1, null, true);

            // Do not mess with the end of the class
            if ($tokens[$nextContentIndex]['type'] === 'T_CLOSE_CURLY_BRACKET') {
                return;
            }

            if ($tokens[$nextContentIndex]['line'] - $tokens[$semicolonIndex]['line'] <= 1) {
                $fix = $phpCsFile->addFixableError('Every function/method needs a newline afterwards', $closingParenthesisIndex, 'Abstract');
                if ($fix) {
                    $phpCsFile->fixer->addNewline($semicolonIndex);
                }
            }

            return;
        }

        $closingBraceIndex = $tokens[$openingBraceIndex]['scope_closer'];

        // Ignore closures
        $nextIndex = $phpCsFile->findNext(Tokens::$emptyTokens, $closingBraceIndex + 1, null, true);
        if (in_array($tokens[$nextIndex]['content'], [';', ',', ')'])) {
            return;
        }

        $nextContentIndex = $phpCsFile->findNext(T_WHITESPACE, $closingBraceIndex + 1, null, true);

        // Do not mess with the end of the class
        if ($tokens[$nextContentIndex]['type'] === 'T_CLOSE_CURLY_BRACKET') {
            return;
        }

        if (!$nextContentIndex || $tokens[$nextContentIndex]['line'] - $tokens[$closingBraceIndex]['line'] <= 1) {
            $fix = $phpCsFile->addFixableError('Every function/method needs a newline afterwards', $closingBraceIndex, 'Concrete');
            if ($fix) {
                $phpCsFile->fixer->addNewline($closingBraceIndex);
            }
        }
    }
}
