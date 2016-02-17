<?php

namespace Spryker\Sniffs\ControlStructures;

use PHP_CodeSniffer_Tokens;
use Spryker\Traits\BasicsTrait;

/**
 * Checks that no YODA conditions (reversed order of natural conditions) are being used.
 */
class ConditionalExpressionOrderSniff implements \PHP_CodeSniffer_Sniff
{

    use BasicsTrait;

    /**
     * @return array
     */
    public function register()
    {
        return PHP_CodeSniffer_Tokens::$comparisonTokens;
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    public function process(\PHP_CodeSniffer_File $phpCsFile, $stackPointer)
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
        if ($this->isGivenKind($tokens[$prevIndex], PHP_CodeSniffer_Tokens::$arithmeticTokens)) {
            return;
        }

        var_dump($tokens[$prevIndex]);
        $fixable = true;
        $error = 'Usage of Yoda conditions is not allowed. Switch the expression order.';
        $prevContent = $tokens[$prevIndex]['content'];

        if (!$this->isGivenKind($tokens[$prevIndex], PHP_CodeSniffer_Tokens::$assignmentTokens)
            && !$this->isGivenKind($tokens[$prevIndex], PHP_CodeSniffer_Tokens::$booleanOperators) && $prevContent !== '('
        ) {
            // Not fixable
            $phpCsFile->addError($error, $stackPointer);
            return;
        }

        $phpCsFile->addError($error, $stackPointer);
        /*
        $fix = $phpCsFile->addFixableError($error, $stackPointer);
        if ($fix) {
            //TODO
        }
        */
    }

}