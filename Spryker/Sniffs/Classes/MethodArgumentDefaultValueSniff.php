<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * In method arguments there must not be arguments with default values before non-default ones.
 *
 * @author Mark Scherer
 * @author Lucas Manzke <lmanzke@outlook.com>
 * @author Gregor Harlan <gharlan@web.de>
 */
class MethodArgumentDefaultValueSniff extends AbstractSprykerSniff
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
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

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

            $fix = $phpcsFile->addFixableError('Invalid optional method argument default value for ' . $token['content'], $i, 'InvalidDefaultValue');

            if ($fix) {
                $commaIndex = $phpcsFile->findPrevious(T_COMMA, $lastArgumentIndex - 1, $startIndex);

                $phpcsFile->fixer->beginChangeset();
                $this->removeDefaultArgument($phpcsFile, $i, $commaIndex - 1);
                $phpcsFile->fixer->endChangeset();
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $startIndex
     * @param int $endIndex
     *
     * @return int|null
     */
    protected function getLastNonDefaultArgumentIndex(File $phpcsFile, $startIndex, $endIndex)
    {
        $tokens = $phpcsFile->getTokens();

        for ($i = $endIndex - 1; $i > $startIndex; --$i) {
            $token = $tokens[$i];

            if ($this->isGivenKind(T_EQUAL, $token)) {
                $i = $phpcsFile->findPrevious(T_VARIABLE, $i - 1);
                $i = $phpcsFile->findPrevious(Tokens::$emptyTokens, $i, $startIndex - 1, true);
                continue;
            }

            if ($this->isGivenKind(T_VARIABLE, $token) && !$this->isEllipsis($phpcsFile, $i)) {
                return $i;
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $variableIndex
     *
     * @return bool
     */
    protected function isEllipsis(File $phpcsFile, $variableIndex)
    {
        $tokens = $phpcsFile->getTokens();

        $prevIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $variableIndex - 1, null, true);
        if (!defined('T_ELLIPSIS')) {
            return $tokens[$prevIndex]['content'] === '.';
        }

        return $this->isGivenKind(T_ELLIPSIS, $tokens[$prevIndex]);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $startIndex
     * @param int $endIndex
     *
     * @return void
     */
    protected function removeDefaultArgument(File $phpcsFile, $startIndex, $endIndex)
    {
        $this->clearWhitespacesBeforeIndex($phpcsFile, $startIndex);
        for ($i = $startIndex; $i <= $endIndex; ++$i) {
            $phpcsFile->fixer->replaceToken($i, '');
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $index Index of "="
     *
     * @return bool
     */
    protected function isTypehintedNullableVariable(File $phpcsFile, $index)
    {
        $tokens = $phpcsFile->getTokens();

        $nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $index + 1, null, true);

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
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $index
     *
     * @return void
     */
    protected function clearWhitespacesBeforeIndex(File $phpcsFile, $index)
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$index - 1]['code'] !== T_WHITESPACE) {
            return;
        }

        $phpcsFile->fixer->replaceToken($index - 1, '');
    }
}
