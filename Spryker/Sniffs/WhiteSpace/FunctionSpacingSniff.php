<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * There should always be newlines around functions/methods.
 */
class FunctionSpacingSniff implements Sniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_FUNCTION];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        $tokens = $phpCsFile->getTokens();

        $level = $tokens[$stackPointer]['level'];
        if ($level < 1) {
            return;
        }

        $openingBraceIndex = $phpCsFile->findNext(T_OPEN_CURLY_BRACKET, $stackPointer + 1);
        // Fix interface methods
        if (!$openingBraceIndex) {
            $openingParenthesisIndex = $phpCsFile->findNext(T_OPEN_PARENTHESIS, $stackPointer + 1);
            $closingParenthesisIndex = $tokens[$openingParenthesisIndex]['parenthesis_closer'];

            $semicolonIndex = $phpCsFile->findNext(T_SEMICOLON, $closingParenthesisIndex + 1);

            $nextContentIndex = $phpCsFile->findNext(T_WHITESPACE, $semicolonIndex + 1, null, true);

            // Do not mess with the end of the class
            if ($tokens[$nextContentIndex]['type'] === 'T_CLOSE_CURLY_BRACKET') {
                return;
            }

            if ($tokens[$nextContentIndex]['line'] - $tokens[$semicolonIndex]['line'] <= 1) {
                $fix = $phpCsFile->addFixableError('Every function/method needs a newline afterwards', $closingParenthesisIndex, 'AbstractAfter');
                if ($fix) {
                    $phpCsFile->fixer->addNewline($semicolonIndex);
                }
            }

            return;
        }

        if (empty($tokens[$openingBraceIndex]['scope_closer'])) {
            return;
        }

        $closingBraceIndex = $tokens[$openingBraceIndex]['scope_closer'];

        // Ignore closures
        $nextIndex = $phpCsFile->findNext(Tokens::$emptyTokens, $closingBraceIndex + 1, null, true);
        if (in_array($tokens[$nextIndex]['content'], [';', ',', ')'], true)) {
            return;
        }

        $nextContentIndex = $phpCsFile->findNext(T_WHITESPACE, $closingBraceIndex + 1, null, true);

        // Do not mess with the end of the class
        if ($tokens[$nextContentIndex]['type'] === 'T_CLOSE_CURLY_BRACKET') {
            return;
        }

        $this->assertNewLineAtTheEnd($phpCsFile, $closingBraceIndex, $nextContentIndex);
        $this->assertNewLineAtTheBeginning($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $closingBraceIndex
     * @param int|null $nextContentIndex
     *
     * @return void
     */
    protected function assertNewLineAtTheEnd(File $phpCsFile, int $closingBraceIndex, ?int $nextContentIndex): void
    {
        $tokens = $phpCsFile->getTokens();

        if (!$nextContentIndex || $tokens[$nextContentIndex]['line'] - $tokens[$closingBraceIndex]['line'] <= 1) {
            $fix = $phpCsFile->addFixableError('Every function/method needs a newline afterwards', $closingBraceIndex, 'ConcreteAfter');
            if ($fix) {
                $phpCsFile->fixer->addNewline($closingBraceIndex);
            }
        }
    }

    /**
     * Asserts newline at the beginning, including the doc block.
     *
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function assertNewLineAtTheBeginning(File $phpCsFile, int $stackPointer): void
    {
        $tokens = $phpCsFile->getTokens();

        $line = $tokens[$stackPointer]['line'];
        $firstTokenInLineIndex = $stackPointer;
        while ($tokens[$firstTokenInLineIndex - 1]['line'] === $line) {
            $firstTokenInLineIndex--;
        }

        $prevContentIndex = $phpCsFile->findPrevious(T_WHITESPACE, $firstTokenInLineIndex - 1, null, true);
        if ($tokens[$prevContentIndex]['code'] === T_DOC_COMMENT_CLOSE_TAG) {
            $firstTokenInLineIndex = $tokens[$prevContentIndex]['comment_opener'];
            $line = $tokens[$firstTokenInLineIndex]['line'];
            while ($tokens[$firstTokenInLineIndex - 1]['line'] === $line) {
                $firstTokenInLineIndex--;
            }
        }

        $prevContentIndex = $phpCsFile->findPrevious(T_WHITESPACE, $firstTokenInLineIndex - 1, null, true);

        // Do not mess with the start of the class
        if ($tokens[$prevContentIndex]['type'] === 'T_OPEN_CURLY_BRACKET') {
            return;
        }

        if ($tokens[$prevContentIndex]['line'] < $tokens[$firstTokenInLineIndex]['line'] - 1) {
            return;
        }

        $fix = $phpCsFile->addFixableError('Every function/method needs a newline before', $firstTokenInLineIndex, 'ConcreteBefore');
        if ($fix) {
            $phpCsFile->fixer->beginChangeset();
            $phpCsFile->fixer->addNewline($prevContentIndex);
            $phpCsFile->fixer->endChangeset();
        }
    }
}
