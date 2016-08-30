<?php

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Tools\Traits\CommentingTrait;

/**
 * Method doc blocks should have a consistent grouping of tag types.
 * They also should have a single newline between description and tags.
 */
class DocBlockTagGroupingSniff extends AbstractSprykerSniff
{

    use CommentingTrait;

    /**
     * @inheritDoc
     */
    public function register()
    {
        return [T_FUNCTION];
    }

    /**
     * @inheritDoc
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Don't mess with closures
        $prevIndex = $phpcsFile->findPrevious(PHP_CodeSniffer_Tokens::$emptyTokens, $stackPtr - 1, null, true);
        if (!$this->isGivenKind(PHP_CodeSniffer_Tokens::$methodPrefixes, $tokens[$prevIndex])) {
            return;
        }

        $docBlockEndIndex = $this->findRelatedDocBlock($phpcsFile, $stackPtr);
        if (!$docBlockEndIndex) {
            return;
        }

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        $hasInheritDoc = $this->hasInheritDoc($phpcsFile, $docBlockStartIndex, $docBlockEndIndex);
        if ($hasInheritDoc) {
            return;
        }

        $this->checkFirstAnnotationTag($phpcsFile, $docBlockStartIndex, $docBlockEndIndex);
        $this->checkLastAnnotationTag($phpcsFile, $docBlockStartIndex, $docBlockEndIndex);
        $this->checkAnnotationTagGrouping($phpcsFile, $docBlockStartIndex, $docBlockEndIndex);
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return void
     */
    protected function checkFirstAnnotationTag(PHP_CodeSniffer_File $phpCsFile, $docBlockStartIndex, $docBlockEndIndex)
    {
        $tokens = $phpCsFile->getTokens();

        $nextIndex = $phpCsFile->findNext(T_DOC_COMMENT_TAG, $docBlockStartIndex + 1, $docBlockEndIndex);
        if (!$nextIndex) {
            return;
        }

        $prevIndex = $phpCsFile->findPrevious(T_DOC_COMMENT_STRING, $nextIndex - 1, $docBlockStartIndex + 1);
        if (!$prevIndex) {
            $this->checkBeginningOfDocBlock($phpCsFile, $docBlockStartIndex, $nextIndex);
            return;
        }

        $diff = $tokens[$nextIndex]['line'] - $tokens[$prevIndex]['line'];
        if ($diff === 2) {
            return;
        }

        $fix = $phpCsFile->addFixableError('Expected 1 extra new line before tags, got ' . ($diff - 1), $nextIndex);
        if (!$fix) {
            return;
        }

        if ($diff > 2) {
            $phpCsFile->fixer->beginChangeset();

            for ($i = $prevIndex; $i < $nextIndex; $i++) {
                if ($tokens[$i]['line'] <= $tokens[$prevIndex]['line'] + 1 || $tokens[$i]['line'] >= $tokens[$nextIndex]['line']) {
                    continue;
                }
                $phpCsFile->fixer->replaceToken($i, '');
            }

            $phpCsFile->fixer->endChangeset();

            return;
        }

        $i = $nextIndex;
        while ($tokens[$i]['line'] === $tokens[$nextIndex]['line']) {
            $i--;
        }

        $phpCsFile->fixer->beginChangeset();

        $indentation = $this->getIndentationWhitespace($phpCsFile, $docBlockEndIndex);
        $phpCsFile->fixer->addContentBefore($i, $indentation . '*');
        $phpCsFile->fixer->addNewlineBefore($i);

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return void
     */
    protected function checkLastAnnotationTag(PHP_CodeSniffer_File $phpCsFile, $docBlockStartIndex, $docBlockEndIndex)
    {
        $tokens = $phpCsFile->getTokens();

        $prevIndex = $phpCsFile->findPrevious([T_DOC_COMMENT_TAG, T_DOC_COMMENT_STRING], $docBlockEndIndex - 1, $docBlockStartIndex);
        if (!$prevIndex) {
            return;
        }

        $diff = $tokens[$docBlockEndIndex]['line'] - $tokens[$prevIndex]['line'];
        if ($diff < 2) {
            return;
        }

        $fix = $phpCsFile->addFixableError('Expected no extra blank line after tags, got ' . ($diff - 1), $prevIndex);
        if (!$fix) {
            return;
        }

        $phpCsFile->fixer->beginChangeset();

        for ($i = $prevIndex; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['line'] <= $tokens[$prevIndex]['line'] || $tokens[$i]['line'] >= $tokens[$docBlockEndIndex]['line']) {
                continue;
            }
            $phpCsFile->fixer->replaceToken($i, '');
        }

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $docBlockStartIndex
     * @param int $nextIndex
     * @return void
     */
    protected function checkBeginningOfDocBlock(PHP_CodeSniffer_File $phpCsFile, $docBlockStartIndex, $nextIndex)
    {
        $tokens = $phpCsFile->getTokens();

        $diff = $tokens[$nextIndex]['line'] - $tokens[$docBlockStartIndex]['line'];
        if ($diff === 1) {
            return;
        }

        $fix = $phpCsFile->addFixableError('Expected no extra blank line before tags, got ' . ($diff - 1), $nextIndex);
        if ($fix) {
            return;
        }

        $phpCsFile->fixer->beginChangeset();

        for ($i = $docBlockStartIndex; $i < $nextIndex; $i++) {
            if ($tokens[$i]['line'] <= $tokens[$docBlockStartIndex]['line'] || $tokens[$i]['line'] >= $tokens[$nextIndex]['line']) {
                continue;
            }
            $phpCsFile->fixer->replaceToken($i, '');
        }

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     * @return void
     */
    protected function checkAnnotationTagGrouping(PHP_CodeSniffer_File $phpcsFile, $docBlockStartIndex, $docBlockEndIndex)
    {
        //TODO
    }

}
