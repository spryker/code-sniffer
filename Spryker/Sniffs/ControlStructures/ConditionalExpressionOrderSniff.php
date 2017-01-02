<?php

namespace Spryker\Sniffs\ControlStructures;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Sniff;
use PHP_CodeSniffer_Tokens;
use Spryker\Traits\BasicsTrait;

/**
 * Checks that no YODA conditions (reversed order of natural conditions) are being used.
 */
class ConditionalExpressionOrderSniff implements PHP_CodeSniffer_Sniff
{

    use BasicsTrait;

    /**
     * @inheritdoc
     */
    public function register()
    {
        return PHP_CodeSniffer_Tokens::$comparisonTokens;
    }

    /**
     * @inheritdoc
     */
    public function process(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $tokens = $phpCsFile->getTokens();

        $prevIndex = $phpCsFile->findPrevious(PHP_CodeSniffer_Tokens::$emptyTokens, ($stackPointer - 1), null, true);
        if (!in_array($tokens[$prevIndex]['code'], [T_TRUE, T_FALSE, T_NULL, T_LNUMBER, T_CONSTANT_ENCAPSED_STRING])) {
            return;
        }

        $leftIndexEnd = $prevIndex;
        $leftIndexStart = $prevIndex;

        $prevIndex = $phpCsFile->findPrevious(PHP_CodeSniffer_Tokens::$emptyTokens, ($prevIndex - 1), null, true);
        if (!$prevIndex) {
            return;
        }
        if ($this->isGivenKind(PHP_CodeSniffer_Tokens::$arithmeticTokens, $tokens[$prevIndex])) {
            return;
        }
        if ($this->isGivenKind([T_STRING_CONCAT], $tokens[$prevIndex])) {
            return;
        }

        $fixable = true;
        $error = 'Usage of Yoda conditions is not allowed. Switch the expression order.';
        $prevContent = $tokens[$prevIndex]['content'];

        if (!$this->isGivenKind(PHP_CodeSniffer_Tokens::$assignmentTokens, $tokens[$prevIndex])
            && !$this->isGivenKind(PHP_CodeSniffer_Tokens::$booleanOperators, $tokens[$prevIndex]) && $prevContent !== '('
        ) {
            // Not fixable
            $phpCsFile->addError($error, $stackPointer);
            return;
        }

        //TODO
    }

    /**
     * @param array $tokens
     * @param int $comparisonIndex
     *
     * @return int
     */
    protected function getComparisonValue($tokens, $comparisonIndex)
    {
        $comparisonIndexValue = $tokens[$comparisonIndex]->getContent();
        $operatorsToMap = [T_GREATER_THAN, T_SMALLER_THAN, T_IS_GREATER_OR_EQUAL, T_IS_SMALLER_OR_EQUAL];
        if (in_array($tokens[$comparisonIndex]->getId(), $operatorsToMap, true)) {
            $mapping = [
                T_GREATER_THAN => '<',
                T_SMALLER_THAN => '>',
                T_IS_GREATER_OR_EQUAL => '<=',
                T_IS_SMALLER_OR_EQUAL => '>=',
            ];
            $comparisonIndexValue = $mapping[$tokens[$comparisonIndex]->getId()];

            return $comparisonIndexValue;
        }

        return $comparisonIndexValue;
    }

    /**
     * @param array $tokens
     * @param int $index
     *
     * @return int
     */
    protected function detectRightEnd($tokens, $index)
    {
        $rightEndIndex = $index;
        $nextIndex = $index;
        $max = null;
        $braceCounter = 0;
        if ($tokens[$index]->getContent() === '(') {
            ++$braceCounter;
            $braceEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $index);

            return $braceEndIndex;
        }

        while (true) {
            $nextIndex = $tokens->getNextMeaningfulToken($nextIndex);
            if ($nextIndex === null) {
                return $rightEndIndex;
            }

            $token = $tokens[$nextIndex];
            $content = $token->getContent();

            if (!$token->isCast()
                && !$token->isGivenKind([T_VARIABLE, T_OBJECT_OPERATOR, T_STRING, T_CONST, T_DOUBLE_COLON, T_CONSTANT_ENCAPSED_STRING, T_LNUMBER])
                && !in_array($content, ['(', ')', '[', ']'], true)
            ) {
                return $rightEndIndex;
            }

            if ($content === ')') {
                --$braceCounter;
            }
            if ($braceCounter < 0) {
                return $rightEndIndex;
            }

            if ($content === '(') {
                $nextIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $nextIndex);
            }

            if ($max !== null && $nextIndex > $max) {
                return $rightEndIndex;
            }

            $rightEndIndex = $nextIndex;
        }

        return $rightEndIndex;
    }

    /**
     * @param int $index
     * @param int $leftIndexStart
     * @param int int $leftIndexEnd
     * @param int $rightIndexStart
     * @param int $rightIndexEnd
     *
     * @return void
     */
    protected function applyFix($tokens, $index, $leftIndexStart, $leftIndexEnd, $rightIndexStart, $rightIndexEnd)
    {
        // Check if we need to inverse comparison operator
        $comparisonValue = $this->getComparisonValue($tokens, $index);

        $leftValue = '';
        for ($i = $leftIndexStart; $i <= $leftIndexEnd; ++$i) {
            $leftValue .= $tokens[$i]->getContent();
            $tokens[$i]->setContent('');
        }
        $rightValue = '';
        for ($i = $rightIndexStart; $i <= $rightIndexEnd; ++$i) {
            $rightValue .= $tokens[$i]->getContent();
            $tokens[$i]->setContent('');
        }

        $tokens[$index]->setContent($comparisonValue);
        $tokens[$leftIndexEnd]->setContent($rightValue);
        $tokens[$rightIndexStart]->setContent($leftValue);
    }

}
