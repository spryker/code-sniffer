<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * There should be no empty newline at the beginning of each body.
 */
class EmptyEnclosingLineSniff extends AbstractSprykerSniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_CLASS,
            T_INTERFACE,
            T_TRAIT,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $errorData = [strtolower($tokens[$stackPtr]['content'])];

        if (isset($tokens[$stackPtr]['scope_opener']) === false) {
            return;
        }

        $curlyBraceStartIndex = $tokens[$stackPtr]['scope_opener'];
        $curlyBraceEndIndex = $tokens[$stackPtr]['scope_closer'];

        $lastContentIndex = $phpcsFile->findPrevious(T_WHITESPACE, ($curlyBraceEndIndex - 1), $stackPtr, true);
        if (!$lastContentIndex) {
            return;
        }

        if ($lastContentIndex === $curlyBraceStartIndex) {
            // Single new line for empty body
            if ($tokens[$curlyBraceEndIndex]['line'] === $tokens[$curlyBraceStartIndex]['line'] + 1) {
                return;
            }

            $error = 'Closing brace of an empty %s must have only a single new line between curly brackets';

            $fix = $phpcsFile->addFixableError($error, $curlyBraceEndIndex, 'CloseBraceNewLine', $errorData);
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();

                if ($curlyBraceEndIndex - $curlyBraceStartIndex === 1) {
                    $phpcsFile->fixer->addNewline($curlyBraceStartIndex);
                } else {
                    $phpcsFile->fixer->replaceToken($curlyBraceStartIndex + 1, '');
                }

                $phpcsFile->fixer->endChangeset();
            }

            return;
        }

        $firstContentIndex = $phpcsFile->findNext(T_WHITESPACE, ($curlyBraceStartIndex + 1), $lastContentIndex, true);
        if (!$firstContentIndex) {
            return;
        }
        $beginningOfLine = $this->getFirstTokenOfLine($tokens, $firstContentIndex);

        $contentLine = $tokens[$firstContentIndex]['line'];
        $braceLine = $tokens[$curlyBraceStartIndex]['line'];

        if ($contentLine !== $braceLine + 1) {
            $phpcsFile->recordMetric($stackPtr, 'Class opening brace placement', 'lines');
            $error = 'Opening brace of a %s must have only one new line between itself and the first content.';

            $fix = $phpcsFile->addFixableError($error, $curlyBraceStartIndex, 'OpenBraceNewLine', $errorData);
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();

                if ($contentLine < $braceLine + 1) {
                    $phpcsFile->fixer->addNewline($curlyBraceStartIndex);
                } else {
                    for ($i = $curlyBraceStartIndex + 1; $i < $beginningOfLine - 1; $i++) {
                        $phpcsFile->fixer->replaceToken($i, '');
                    }
                }

                $phpcsFile->fixer->endChangeset();
            }
        }
    }
}
