<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Ensures no additional newlines between doc block and class.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockSpacingSniff implements Sniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_CLASS, T_INTERFACE, T_TRAIT, T_FUNCTION, T_PROPERTY];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $line = $tokens[$stackPtr]['line'];
        $beginningOfLine = $stackPtr;
        while (!empty($tokens[$beginningOfLine - 1]) && $tokens[$beginningOfLine - 1]['line'] === $line) {
            $beginningOfLine--;
        }
        $previousIndex = $phpcsFile->findPrevious(T_WHITESPACE, ($beginningOfLine - 1), null, true);

        if (!$previousIndex || $tokens[$previousIndex]['code'] !== T_DOC_COMMENT_CLOSE_TAG) {
            return;
        }

        if ($tokens[$previousIndex]['line'] >= $line - 1) {
            return;
        }

        $error = 'Additional newline detected between doc block and code';
        $fix = $phpcsFile->addFixableError($error, $previousIndex, 'InvalidSpacing');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        $index = $previousIndex + 1;

        while ($index < $beginningOfLine - 1) {
            $phpcsFile->fixer->replaceToken($index, '');
            $index++;
        }

        $phpcsFile->fixer->endChangeset();
    }
}
