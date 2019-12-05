<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * In class properties, a default null argument can be removed.
 *
 * @author Mark Scherer
 */
class PropertyDefaultValueSniff extends AbstractSprykerSniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_VARIABLE];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $visibilityIndex = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);
        if (!$visibilityIndex) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        if (!$this->isGivenKind([T_PUBLIC, T_PRIVATE, T_PROTECTED, T_ABSTRACT], $tokens[$visibilityIndex])) {
            return;
        }

        $semicolonIndex = $phpcsFile->findNext(T_SEMICOLON, $stackPtr + 1);
        if (!$semicolonIndex || $semicolonIndex === $stackPtr + 1) {
            return;
        }

        $defaultValueIndex = $phpcsFile->findPrevious(T_WHITESPACE, $semicolonIndex - 1, $stackPtr + 1, true);
        if (!$defaultValueIndex || $tokens[$defaultValueIndex]['code'] !== T_NULL) {
            return;
        }

        $fix = $phpcsFile->addFixableError('Unnecessary default value for `' . $tokens[$stackPtr]['content'] . '`', $defaultValueIndex, 'Unnecessary');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        for ($i = $stackPtr + 1; $i < $semicolonIndex; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }
        $phpcsFile->fixer->endChangeset();
    }
}
