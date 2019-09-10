<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Checks that control structures have the correct spacing around them.
 */
class ControlStructureSpacingSniff implements Sniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            //T_IF,
            //T_WHILE,
            //T_FOREACH,
            //T_FOR,
            //T_SWITCH,
            //T_DO,
            //T_ELSE,
            //T_ELSEIF,
            T_TRY,
            T_CATCH,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $this->checkTryToken($phpcsFile, $stackPtr);
        $this->checkCatchToken($phpcsFile, $stackPtr);
        // Add more later
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkTryToken(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] !== T_TRY) {
            return;
        }

        $this->expectSingleSpaceAfter($phpcsFile, $stackPtr, 'try');
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkCatchToken(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] !== T_CATCH) {
            return;
        }

        $this->expectSingleSpaceBefore($phpcsFile, $stackPtr, 'catch');
        $this->expectSingleSpaceAfter($phpcsFile, $stackPtr, 'catch');
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     * @param string $tokenName
     *
     * @return void
     */
    protected function expectSingleSpaceBefore(File $phpcsFile, int $stackPtr, string $tokenName): void
    {
        $tokens = $phpcsFile->getTokens();

        $prevIndex = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);
        if ($prevIndex === $stackPtr - 1) {
            $fix = $phpcsFile->addFixableError('Whitespace missing before ' . $tokenName, $stackPtr, 'MissingBefore');
            if ($fix) {
                $phpcsFile->fixer->addContent($prevIndex, ' ');
            }

            return;
        }

        if ($tokens[$stackPtr - 1]['content'] !== ' ') {
            $error = 'Whitespace invalid before ' . $tokenName . ', expected ` `, got `' . $tokens[$stackPtr - 1]['content'] . '`';
            $fix = $phpcsFile->addFixableError($error, $stackPtr, 'InvalidBefore');
            if ($fix) {
                for ($i = $prevIndex + 1; $i < $stackPtr - 1; $i++) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }
                $phpcsFile->fixer->replaceToken($stackPtr - 1, ' ');
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     * @param string $tokenName
     *
     * @return void
     */
    protected function expectSingleSpaceAfter(File $phpcsFile, int $stackPtr, string $tokenName): void
    {
        $tokens = $phpcsFile->getTokens();

        $nextIndex = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
        if ($nextIndex === $stackPtr + 1) {
            $fix = $phpcsFile->addFixableError('Whitespace missing after ' . $tokenName, $stackPtr, 'MissingAfter');
            if ($fix) {
                $phpcsFile->fixer->addContent($stackPtr, ' ');
            }

            return;
        }

        if ($tokens[$stackPtr + 1]['content'] !== ' ') {
            $error = 'Whitespace invalid after ' . $tokenName . ', expected ` `, got `' . $tokens[$stackPtr + 1]['content'] . '`';
            $fix = $phpcsFile->addFixableError($error, $stackPtr, 'InvalidAfter');
            if ($fix) {
                for ($i = $nextIndex - 1; $i > $stackPtr + 1; $i--) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }
                $phpcsFile->fixer->replaceToken($stackPtr + 1, ' ');
            }
        }
    }
}
