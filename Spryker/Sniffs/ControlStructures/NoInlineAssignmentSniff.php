<?php

/**
 * (c) Spryker Systems GmbH copyright protected.
 */

namespace Spryker\Sniffs\ControlStructures;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Inline/conditional assignment is not allowed. Extract into an own line above.
 */
class NoInlineAssignmentSniff extends AbstractSprykerSniff
{

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        // We skip T_FOR, T_WHILE for now as they can have valid inline assignment
        return [T_FOREACH, T_IF, T_SWITCH, T_OBJECT_OPERATOR, T_DOUBLE_COLON];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer_File $phpcsFile All the tokens found in the document.
     * @param int $stackPtr The position of the current token
     *    in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['code'] === T_OBJECT_OPERATOR || $tokens[$stackPtr]['code'] === T_DOUBLE_COLON) {
            $this->checkMethodCalls($phpcsFile, $stackPtr);
            return;
        }

        $openingBraceIndex = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($stackPtr + 1), null, true);
        if (!$openingBraceIndex) {
            return;
        }
        if (empty($tokens[$openingBraceIndex]['parenthesis_closer'])) {
            return;
        }

        $closingBraceIndex = $tokens[$openingBraceIndex]['parenthesis_closer'];

        $hasInlineAssignment = $this->contains($phpcsFile, $openingBraceIndex, $closingBraceIndex, T_EQUAL);
        if (!$hasInlineAssignment) {
            return;
        }

        $phpcsFile->addError('Inline/Conditional assignment not allowed', $stackPtr);
    }

    /**
     * @param \PHP_CodeSniffer_File $phpcsFile
     * @param int $startIndex
     * @param int $endIndex
     * @param int &$indexEqualSign
     *
     * @return bool
     */
    protected function isFixableInlineAssignment(PHP_CodeSniffer_File $phpcsFile, $startIndex, $endIndex, &$indexEqualSign)
    {
        $tokens = $phpcsFile->getTokens();

        $hasInlineAssignment = false;
        for ($i = $startIndex; $i < $endIndex; $i++) {
            $currentToken = $tokens[$i];

            // We need to skip for complex assignments
            if ($this->isGivenKind(PHP_CodeSniffer_Tokens::$booleanOperators, $tokens[$currentToken])) {
                $hasInlineAssignment = false;
                break;
            }

            // Negations we also cannot handle just yet
            if ($tokens[$currentToken]['code'] === T_BOOLEAN_NOT) {
                $hasInlineAssignment = false;
                break;
            }

            // Comparison inside is also more complex
            if ($this->isGivenKind(PHP_CodeSniffer_Tokens::$comparisonTokens, $tokens[$currentToken])) {
                $hasInlineAssignment = false;
                break;
            }

            $indexEqualSign = $i;
            $hasInlineAssignment = true;
        }

        return $hasInlineAssignment;
    }

    /**
     * @param \PHP_CodeSniffer_File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkMethodCalls(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $openingBraceIndex = $phpcsFile->findNext(T_OPEN_PARENTHESIS, ($stackPtr + 1), $stackPtr + 4);
        if (!$openingBraceIndex) {
            return;
        }
        if (empty($tokens[$openingBraceIndex]['parenthesis_closer'])) {
            return;
        }

        $closingBraceIndex = $tokens[$openingBraceIndex]['parenthesis_closer'];

        $hasInlineAssignment = $this->contains($phpcsFile, T_EQUAL, $openingBraceIndex + 1, $closingBraceIndex - 1);
        if (!$hasInlineAssignment) {
            return;
        }

        $phpcsFile->addError('Inline assignment not allowed', $stackPtr);
    }

}
