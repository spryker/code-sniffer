<?php

/**
 * (c) Spryker Systems GmbH copyright protected.
 */

namespace Spryker\Sniffs\ControlStructures;

use PHP_CodeSniffer_Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Inline/conditional assignment is not allowed. Extract into an own line above.
 */
class NoInlineAssignment extends AbstractSprykerSniff
{

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register() {
        // We skip T_FOR, T_WHILE for now as they can have valid inline assignment
        return [T_FOREACH, T_IF, T_SWITCH];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer_File $phpcsFile All the tokens found in the document.
     * @param int $stackPtr The position of the current token
     *    in the stack passed in $tokens.
     * @return void
     */
    public function process(\PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
        $tokens = $phpcsFile->getTokens();

        $openingBraceIndex = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($stackPtr + 1), null, true);

        //var_dump($tokens[$openingBraceIndex]); die();

        $closingBraceIndex = $tokens[$openingBraceIndex]['close_parenthesis'];

        $hasInlineAssignment = $this->contains($phpcsFile, $openingBraceIndex, $closingBraceIndex, T_EQUAL);
        if (!$hasInlineAssignment) {
            return;
        }

        return;
        //die('C');

        // Extract to own $var into line above
        $string = '';
        $var = '';
        for ($i = $startIndex + 1; $i < $endIndex; ++$i) {
            $string .= $tokens[$i]->getContent();
            if ($i < $indexEqualSign) {
                $var .= $tokens[$i]->getContent();
            }

            $tokens[$i]->clear();
        }

        $string .= ';';

        $tokens[$i - 1]->setContent(trim($var));

        $content = $tokens[$index]->getContent();
        $indent = Utils::calculateTrailingWhitespaceIndent($tokens[$index - 1]);
        $content = $indent . $content;

        $content = $string . PHP_EOL . $content;
        $tokens[$index]->setContent($content);
    }

    /**
     * @param \PHP_CodeSniffer_File $phpcsFile
     * @param int $startIndex
     * @param int $endIndex
     * @param int &$indexEqualSign
     *
     * @return bool
     */
    protected function isFixableInlineAssignment(\PHP_CodeSniffer_File $phpcsFile, $startIndex, $endIndex, &$indexEqualSign)
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

}
