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
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_ISSET, T_EMPTY];
    }

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

        $valueIndex = $phpcsFile->findNext(Tokens::$emptyTokens, ($openingBraceIndex + 1), ($closingBraceIndex - 1), true);
        if (!$valueIndex) {
            return;
        }

        $lastValueIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($closingBraceIndex - 1), $valueIndex, true) ?: $valueIndex;

        $validTokens = [
            T_CLOSE_SQUARE_BRACKET,
        ];
        $validSilencing = false;
        for ($i = $valueIndex; $i <= $lastValueIndex; $i++) {
            if (in_array($tokens[$i]['code'], $validTokens, true)) {
                $validSilencing = true;

                break;
            }
        }

        if ($validSilencing) {
            return;
        }

        $inverted = false;
        $previousTokenIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
        if ($previousTokenIndex && $tokens[$previousTokenIndex]['code'] === T_BOOLEAN_NOT) {
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

        if ($inverted && $previousTokenIndex) {
            $assignmentTokenIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($previousTokenIndex - 1), null, true);
            if ($assignmentTokenIndex && in_array($tokens[$assignmentTokenIndex]['code'], [T_EQUAL, T_RETURN], true)) {
                $phpcsFile->addError($message, $stackPtr, 'InvalidEmpty');

                return;
            }
        }

        $fix = $phpcsFile->addFixableError($message, $stackPtr, 'FixableEmpty');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();

        if ($inverted) {
            $phpcsFile->fixer->replaceToken($previousTokenIndex, '');
            $phpcsFile->fixer->replaceToken($stackPtr, '');
        } else {
            $phpcsFile->fixer->replaceToken($stackPtr, '!');
        }

        $phpcsFile->fixer->replaceToken($openingBraceIndex, '');
        $phpcsFile->fixer->replaceToken($closingBraceIndex, '');

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkMethodCalls(File $phpcsFile, int $stackPtr): void
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

        $phpcsFile->addError('Inline assignment not allowed', $stackPtr, 'NoInlineAssignment');
    }
}
