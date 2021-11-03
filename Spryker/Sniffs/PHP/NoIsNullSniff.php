<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Always use strict null check instead if is_null() method invocation.
 */
class NoIsNullSniff extends AbstractSprykerSniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_STRING];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $wrongTokens = [T_FUNCTION, T_OBJECT_OPERATOR, T_NEW, T_DOUBLE_COLON];

        $tokens = $phpcsFile->getTokens();

        $tokenContent = $tokens[$stackPtr]['content'];
        if (strtolower($tokenContent) !== 'is_null') {
            return;
        }

        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if (!$previous || in_array($tokens[$previous]['code'], $wrongTokens, true)) {
            return;
        }

        $openingBraceIndex = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if (!$openingBraceIndex || $tokens[$openingBraceIndex]['type'] !== 'T_OPEN_PARENTHESIS') {
            return;
        }

        $closingBraceIndex = $tokens[$openingBraceIndex]['parenthesis_closer'];

        $error = $tokenContent . '() found, should be strict === null check.';

        $possibleCastIndex = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);

        if (!$possibleCastIndex) {
            return;
        }

        $negated = false;
        if ($tokens[$possibleCastIndex]['code'] === T_BOOLEAN_NOT) {
            $negated = true;
        }
        // We dont want to fix double !!
        if ($negated) {
            $anotherPossibleCastIndex = $phpcsFile->findPrevious(T_WHITESPACE, ($possibleCastIndex - 1), null, true);
            if ($tokens[$anotherPossibleCastIndex]['code'] === T_BOOLEAN_NOT) {
                $phpcsFile->addError($error, $stackPtr, 'DoubleNot');

                return;
            }
        }

        // We don't want to fix stuff with bad inline assignment
        if ($this->contains($phpcsFile, 'T_EQUAL', $openingBraceIndex + 1, $closingBraceIndex - 1)) {
            $phpcsFile->addError($error, $stackPtr, 'NoInlineAssignment');

            return;
        }

        $beginningIndex = $negated ? $possibleCastIndex : $stackPtr;
        $endIndex = $closingBraceIndex;

        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'NoIsNull');
        if ($fix) {
            $needsBrackets = $this->needsBrackets($phpcsFile, $openingBraceIndex, $closingBraceIndex);
            $leadingComparison = $this->hasLeadingComparison($phpcsFile, $beginningIndex);
            $trailingComparison = $this->hasTrailingComparison($phpcsFile, $closingBraceIndex);

            if ($leadingComparison) {
                $possibleBeginningIndex = $this->findUnnecessaryLeadingComparisonStart($phpcsFile, $beginningIndex);
                if ($possibleBeginningIndex !== null) {
                    $beginningIndex = $possibleBeginningIndex;
                    $leadingComparison = false;
                    if ($tokens[$beginningIndex]['code'] === T_FALSE) {
                        $negated = !$negated;
                    }
                }
            }

            if ($trailingComparison) {
                $possibleEndIndex = $this->findUnnecessaryLeadingComparisonStart($phpcsFile, $endIndex);
                if ($possibleEndIndex !== null) {
                    $endIndex = $possibleEndIndex;
                    $trailingComparison = false;
                    if ($tokens[$endIndex]['code'] === T_FALSE) {
                        $negated = !$negated;
                    }
                }
            }

            if (!$needsBrackets && ($leadingComparison || $this->leadRequiresBrackets($phpcsFile, $beginningIndex))) {
                $needsBrackets = true;
            }
            if (!$needsBrackets && $trailingComparison) {
                $needsBrackets = true;
            }

            $comparisonString = ' ' . ($negated ? '!' : '=') . '== null';

            $phpcsFile->fixer->beginChangeset();

            if ($beginningIndex !== $stackPtr) {
                for ($i = $beginningIndex; $i < $stackPtr; $i++) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }
            }
            if ($endIndex !== $closingBraceIndex) {
                for ($i = $endIndex; $i > $closingBraceIndex; $i--) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }
            }

            $phpcsFile->fixer->replaceToken($stackPtr, '');
            if (!$needsBrackets) {
                $phpcsFile->fixer->replaceToken($openingBraceIndex, '');
                $phpcsFile->fixer->replaceToken($closingBraceIndex, $comparisonString);
            } else {
                $phpcsFile->fixer->replaceToken($closingBraceIndex, $comparisonString . ')');
            }

            $phpcsFile->fixer->endChangeset();
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $index
     *
     * @return bool
     */
    protected function leadRequiresBrackets(File $phpcsFile, int $index): bool
    {
        $tokens = $phpcsFile->getTokens();

        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($index - 1), null, true);
        if (!$previous) {
            return false;
        }

        if ($this->isCast($previous)) {
            return true;
        }
        if (in_array($tokens[$previous]['code'], Tokens::$arithmeticTokens, true)) {
            return true;
        }

        return false;
    }

    /**
     * @param int $index
     *
     * @return bool
     */
    protected function isCast(int $index): bool
    {
        return in_array($index, Tokens::$castTokens, true);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $index
     *
     * @return int|null
     */
    protected function findUnnecessaryLeadingComparisonStart(File $phpcsFile, int $index): ?int
    {
        $tokens = $phpcsFile->getTokens();

        $previous = (int)$phpcsFile->findPrevious(T_WHITESPACE, ($index - 1), null, true);
        if (!$previous || !in_array($tokens[$previous]['code'], [T_IS_IDENTICAL, T_IS_NOT_IDENTICAL], true)) {
            return null;
        }

        $previous = (int)$phpcsFile->findPrevious(T_WHITESPACE, ($previous - 1), null, true);
        if (!$previous || !in_array($tokens[$previous]['code'], [T_TRUE, T_FALSE], true)) {
            return null;
        }

        return $previous;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $index
     *
     * @return int|null
     */
    protected function findUnnecessaryTrailingComparisonEnd(File $phpcsFile, int $index): ?int
    {
        $tokens = $phpcsFile->getTokens();

        $next = (int)$phpcsFile->findNext(T_WHITESPACE, ($index + 1), null, true);
        if (!$next || !in_array($tokens[$next]['code'], [T_IS_IDENTICAL, T_IS_NOT_IDENTICAL], true)) {
            return null;
        }

        $next = (int)$phpcsFile->findPrevious(T_WHITESPACE, ($next - 1), null, true);
        if (!$next || !in_array($tokens[$next]['code'], [T_TRUE, T_FALSE], true)) {
            return null;
        }

        return $next;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return bool
     */
    protected function hasLeadingComparison(File $phpcsFile, int $stackPtr): bool
    {
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);

        if (!$previous) {
            return false;
        }

        return $this->isComparison($phpcsFile, $previous);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return bool
     */
    protected function hasTrailingComparison(File $phpcsFile, int $stackPtr): bool
    {
        $next = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);

        if (!$next) {
            return false;
        }

        return $this->isComparison($phpcsFile, $next);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $index
     *
     * @return bool
     */
    protected function isComparison(File $phpcsFile, int $index): bool
    {
        $tokens = $phpcsFile->getTokens();

        $whitelist = Tokens::$equalityTokens;
        if (in_array($tokens[$index]['code'], $whitelist, true)) {
            return true;
        }
        if (in_array($tokens[$index]['type'], $whitelist, true)) {
            return true;
        }

        return false;
    }
}
