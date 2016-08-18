<?php

namespace Spryker\Sniffs\Whitespace;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Sniff;

/**
 * There should be an empty newline at the beginning and end of each body.
 * Unless it is empty.
 */
class EmptyEnclosingLineSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * @inheritdoc
     */
    public function register()
    {
        return [
            T_CLASS,
            T_INTERFACE,
            T_TRAIT,
        ];
    }

    /**
     * @inheritdoc
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $errorData = [strtolower($tokens[$stackPtr]['content'])];

        if (isset($tokens[$stackPtr]['scope_opener']) === false) {
            $error = 'Possible parse error: %s missing opening or closing brace';
            $phpcsFile->addWarning($error, $stackPtr, 'MissingBrace', $errorData);
            return;
        }

        $curlyBraceStartIndex = $tokens[$stackPtr]['scope_opener'];
        $curlyBraceEndIndex = $tokens[$stackPtr]['scope_closer'];

        $lastContentIndex = $phpcsFile->findPrevious(T_WHITESPACE, ($curlyBraceEndIndex - 1), $stackPtr, true);

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

        $contentLine = $tokens[$lastContentIndex]['line'];
        $braceLine = $tokens[$curlyBraceEndIndex]['line'];

        if ($braceLine !== $contentLine + 2) {
            $phpcsFile->recordMetric($stackPtr, 'Class closing brace placement', 'lines');
            $error = 'Closing brace of a %s must have one extra new line between itself and the last content.';

            $fix = $phpcsFile->addFixableError($error, $curlyBraceEndIndex, 'CloseBraceNewLine', $errorData);
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();

                if ($braceLine < $contentLine + 2) {
                    $phpcsFile->fixer->addNewlineBefore($curlyBraceEndIndex);
                } else {
                    for ($i = $lastContentIndex + 2; $i < $curlyBraceEndIndex - 1; $i++) {
                        $phpcsFile->fixer->replaceToken($i, '');
                    }
                }

                $phpcsFile->fixer->endChangeset();
            }
        }

        $firstContentIndex = $phpcsFile->findNext(T_WHITESPACE, ($curlyBraceStartIndex + 1), $lastContentIndex, true);

        $contentLine = $tokens[$firstContentIndex]['line'];
        $braceLine = $tokens[$curlyBraceStartIndex]['line'];

        if ($contentLine !== $braceLine + 2) {
            $phpcsFile->recordMetric($stackPtr, 'Class opening brace placement', 'lines');
            $error = 'Opening brace of a %s must have one extra new line between itself and the first content.';

            $fix = $phpcsFile->addFixableError($error, $curlyBraceStartIndex, 'OpenBraceNewLine', $errorData);
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();

                if ($contentLine < $braceLine + 2) {
                    $phpcsFile->fixer->addNewline($curlyBraceStartIndex);
                } else {
                    for ($i = $curlyBraceStartIndex + 1; $i < $firstContentIndex - 1; $i++) {
                        $phpcsFile->fixer->replaceToken($i, '');
                    }
                }

                $phpcsFile->fixer->endChangeset();
            }
        }
    }

}
