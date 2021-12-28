<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Cloaking checks are only valid for cases where the key can be undefined.
 * It is not allowed on variables (define them first) or functions/methods (use *exists check).
 */
class DisallowCloakingCheckSniff extends AbstractSprykerSniff
{
    /**
     * Use this to make this sniff more strict regarding object var references.
     *
     * @var bool
     */
    public $strict = false;

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_ISSET, T_EMPTY];
    }

    /**
     * @var array<string>
     */
    protected $validTokens = [
        T_CLOSE_SQUARE_BRACKET,
        T_CLOSE_CURLY_BRACKET,
    ];

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $openingBraceIndex = $phpcsFile->findNext(Tokens::$emptyTokens, ($stackPtr + 1), null, true);
        if (!$openingBraceIndex) {
            return;
        }

        $closingBraceIndex = $tokens[$openingBraceIndex]['parenthesis_closer'];

        $valueIndex = $phpcsFile->findNext(Tokens::$emptyTokens, ($openingBraceIndex + 1), $closingBraceIndex, true);
        if (!$valueIndex) {
            return;
        }

        if ($tokens[$valueIndex]['code'] === T_VARIABLE && strpos($tokens[$valueIndex]['content'], '$_') === 0) {
            return;
        }

        $lastValueIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($closingBraceIndex - 1), $valueIndex, true) ?: $valueIndex;

        $validSilencing = $this->isValidSilencing($phpcsFile, $valueIndex, $lastValueIndex);

        if ($validSilencing) {
            return;
        }

        $inverted = false;
        $previousTokenIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
        if (!$previousTokenIndex) {
            return;
        }

        if ($tokens[$previousTokenIndex]['code'] === T_BOOLEAN_NOT) {
            $inverted = true;
        }

        $message = sprintf('Cloaking `%s()` check is not allowed for non-silencing needs.', $tokens[$stackPtr]['content']);

        if ($tokens[$stackPtr]['content'] === 'isset') {
            $phpcsFile->addError($message, $stackPtr, 'InvalidIsset');

            return;
        }

        $nextTokenIndex = $phpcsFile->findNext(Tokens::$emptyTokens, ($closingBraceIndex + 1), null, true);
        if ($nextTokenIndex && in_array($tokens[$nextTokenIndex]['code'], Tokens::$equalityTokens, true)) {
            $phpcsFile->addError($message, $stackPtr, 'InvalidEmpty');

            return;
        }

        if ($inverted) {
            $fix = $phpcsFile->addFixableError($message, $stackPtr, 'InvalidEmpty');
            if (!$fix) {
                return;
            }

            $isSafeToSkipCast = $this->isSafeToSkipCast($phpcsFile, $stackPtr, $previousTokenIndex);

            $phpcsFile->fixer->beginChangeset();

            $phpcsFile->fixer->replaceToken($previousTokenIndex, '');
            $phpcsFile->fixer->replaceToken($stackPtr, $isSafeToSkipCast ? '' : '(bool)');

            $phpcsFile->fixer->replaceToken($openingBraceIndex, '');
            $phpcsFile->fixer->replaceToken($closingBraceIndex, '');

            $phpcsFile->fixer->endChangeset();

            return;
        }

        $fix = $phpcsFile->addFixableError($message, $stackPtr, 'FixableEmpty');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();

        $phpcsFile->fixer->replaceToken($stackPtr, '!');

        $phpcsFile->fixer->replaceToken($openingBraceIndex, '');
        $phpcsFile->fixer->replaceToken($closingBraceIndex, '');

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     * @param int $previousTokenIndex
     *
     * @return bool
     */
    protected function isSafeToSkipCast(File $phpcsFile, int $stackPtr, int $previousTokenIndex): bool
    {
        $assignmentTokenIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($previousTokenIndex - 1), null, true);

        $tokens = $phpcsFile->getTokens();
        if ($assignmentTokenIndex && in_array($tokens[$assignmentTokenIndex]['code'], [T_EQUAL, T_RETURN], true)) {
            return false;
        }

        $x = $tokens[$stackPtr];
        $nestedParenthesis = $x['nested_parenthesis'] ?? [];
        if (!$nestedParenthesis) {
            return false;
        }

        $keys = array_keys($nestedParenthesis);
        /** @var int $index */
        $index = array_shift($keys);

        $conditionalTokenIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($index - 1), null, true);
        if (!$conditionalTokenIndex || !in_array($tokens[$conditionalTokenIndex]['code'], [T_IF, T_ELSEIF], true)) {
            return false;
        }

        return true;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $valueIndex
     * @param int $lastValueIndex
     *
     * @return bool
     */
    protected function isValidSilencing(File $phpcsFile, int $valueIndex, int $lastValueIndex): bool
    {
        $tokens = $phpcsFile->getTokens();

        $objectOperatorIndex = null;
        for ($i = $valueIndex; $i <= $lastValueIndex; $i++) {
            if (in_array($tokens[$i]['code'], $this->validTokens, true)) {
                return true;
            }

            if ($tokens[$i]['code'] === T_OBJECT_OPERATOR) {
                $objectOperatorIndex = $i;
            }
        }

        if (!$objectOperatorIndex) {
            return false;
        }

        if ($this->strict) {
            return false;
        }

        $prevIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($objectOperatorIndex - 1), $valueIndex, true);
        if ($prevIndex && $tokens[$prevIndex]['code'] === T_VARIABLE && $tokens[$prevIndex]['content'] !== '$this') {
            return true;
        }
        if ($prevIndex && $tokens[$prevIndex]['code'] === T_STRING) {
            return true;
        }

        return false;
    }
}
