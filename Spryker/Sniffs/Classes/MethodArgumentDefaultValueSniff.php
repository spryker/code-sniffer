<?php

/**
 * @author Mark Scherer
 * @author Lucas Manzke <lmanzke@outlook.com>
 * @author Gregor Harlan <gharlan@web.de>
 */

namespace Spryker\Sniffs\Classes;

use PHP_CodeSniffer_Tokens;
use \Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * In method arguments there must not be arguments with default values before non-default ones.
 */
class MethodArgumentDefaultValueSniff extends AbstractSprykerSniff
{

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [T_FUNCTION];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer_File $phpcsFile All the tokens found in the document.
     * @param int $stackPtr The position of the current token
     *    in the stack passed in $tokens.
     * @return void
     */
    public function process(\PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        return;

        $startIndex = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr + 1);

        $endIndex = $tokens[$startIndex]['parenthesis_closer'];

        $lastArgumentIndex = $this->getLastNonDefaultArgumentIndex($phpcsFile, $startIndex, $endIndex);
        if (!$lastArgumentIndex) {
            return;
        }

        for ($i = $lastArgumentIndex; $i > $startIndex; --$i) {
            $token = $tokens[$i];

            if ($this->isGivenKind(T_VARIABLE, $token)) {
                $lastArgumentIndex = $i;
                continue;
            }

            // We skip $this->isTypehintedNullableVariable($tokens, $i) check for now, they are also invalid.
            if (!$this->isGivenKind(T_EQUAL, $token)) {
                continue;
            }

            $fix = $phpcsFile->addFixableError('Invalid optional method argument default value for ' . $token['content'], $i);
            if ($fix) {
                $commaIndex = $phpcsFile->findPrevious(T_COMMA, $lastArgumentIndex - 1, $endIndex);
                $prevIndex = $phpcsFile->findPrevious(PHP_CodeSniffer_Tokens::$emptyTokens, $commaIndex - 1);

                $phpcsFile->fixer->beginChangeset();
                $this->removeDefaultArgument($phpcsFile, $i, $prevIndex);
                $phpcsFile->fixer->endChangeset();
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer_File $phpcsFile
     * @param int $startIndex
     * @param int $endIndex
     *
     * @return int|null
     */
    protected function getLastNonDefaultArgumentIndex(\PHP_CodeSniffer_File $phpcsFile, $startIndex, $endIndex)
    {
        $tokens = $phpcsFile->getTokens();

        for ($i = $endIndex - 1; $i > $startIndex; --$i) {
            $token = $tokens[$i];

            if ($this->isGivenKind(T_EQUAL, $token)) {
                $i = $phpcsFile->findPrevious(PHP_CodeSniffer_Tokens::$emptyTokens, $i, $startIndex - 1, true);
                continue;
            }

            if ($this->isGivenKind(T_VARIABLE, $token) && !$this->isEllipsis($phpcsFile, $i)) {
                return $i;
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer_File $phpcsFile
     * @param int $variableIndex
     *
     * @return bool
     */
    protected function isEllipsis(\PHP_CodeSniffer_File $phpcsFile, $variableIndex)
    {
        $tokens = $phpcsFile->getTokens();

        $prevIndex = $phpcsFile->findPrevious(PHP_CodeSniffer_Tokens::$emptyTokens, $variableIndex - 1, null, true);
//var_dump($prevIndex);die('EEE');
        if (!defined('T_ELLIPSIS')) {
            return $tokens[$prevIndex]['content'] === '.';
        }

        return $this->isGivenKind(T_ELLIPSIS, $tokens[$prevIndex]);
    }

    /**
     * @param int $startIndex
     * @param int $endIndex
     */
    protected function removeDefaultArgument(\PHP_CodeSniffer_File $phpcsFile, $startIndex, $endIndex)
    {
        for ($i = $startIndex; $i <= $endIndex;) {
            $phpcsFile->fixer->replaceToken($i, '');

            $this->clearWhitespacesBeforeIndex($phpcsFile, $i);
            $i = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, $i + 1);
        }
    }

    /**
     * @param \PHP_CodeSniffer_File $phpcsFile
     * @param int $index Index of "="
     *
     * @return bool
     */
    protected function isTypehintedNullableVariable(\PHP_CodeSniffer_File $phpcsFile, $index)
    {
        $tokens = $phpcsFile->getTokens();

        $nextIndex = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, $index + 1, null, true);

        $nextToken = $tokens[$nextIndex];

        if (!$nextToken->equals([T_STRING, 'null'], false)) {
            return false;
        }

        $variableIndex = $tokens->getPrevMeaningfulToken($index);

        $searchTokens = [',', '(', [T_STRING], [CT_ARRAY_TYPEHINT]];
        $typehintKinds = [T_STRING, CT_ARRAY_TYPEHINT];

        if (defined('T_CALLABLE')) {
            $searchTokens[] = [T_CALLABLE];
            $typehintKinds[] = T_CALLABLE;
        }

        $prevIndex = $tokens->getPrevTokenOfKind($variableIndex, $searchTokens);

        return $tokens[$prevIndex]->isGivenKind($typehintKinds);
    }

    /**
     * @param \PHP_CodeSniffer_File $phpcsFile
     * @param int $index
     * @return void
     */
    protected function clearWhitespacesBeforeIndex(\PHP_CodeSniffer_File $phpcsFile, $index)
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$index - 1]['code'] !== T_WHITESPACE) {
            return;
        }

        $phpcsFile->fixer->replaceToken($index - 1, '');
    }

}
