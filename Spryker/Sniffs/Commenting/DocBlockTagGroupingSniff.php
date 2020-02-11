<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Tools\Traits\CommentingTrait;

/**
 * Method doc blocks should have a consistent grouping of tag types.
 * They also should have a single newline between description and tags.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockTagGroupingSniff extends AbstractSprykerSniff
{
    use CommentingTrait;

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
    public function process(File $phpCsFile, $stackPtr)
    {
        $tokens = $phpCsFile->getTokens();

        // Don't mess with closures
        $prevIndex = $phpCsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
        if (!$this->isGivenKind(Tokens::$methodPrefixes, $tokens[$prevIndex])) {
            return;
        }

        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPtr);
        if (!$docBlockEndIndex) {
            return;
        }

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        $this->checkFirstAnnotationTag($phpCsFile, $docBlockStartIndex, $docBlockEndIndex);
        $this->checkLastAnnotationTag($phpCsFile, $docBlockStartIndex, $docBlockEndIndex);
        $this->checkAnnotationTagGrouping($phpCsFile, $docBlockStartIndex, $docBlockEndIndex);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return void
     */
    protected function checkFirstAnnotationTag(File $phpCsFile, int $docBlockStartIndex, int $docBlockEndIndex): void
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

        $fix = $phpCsFile->addFixableError('Expected 1 extra new line before tags, got ' . ($diff - 1), $nextIndex, 'ExtraLineMissing');
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
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return void
     */
    protected function checkLastAnnotationTag(File $phpCsFile, int $docBlockStartIndex, int $docBlockEndIndex): void
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

        $fix = $phpCsFile->addFixableError('Expected no extra blank line after tags, got ' . ($diff - 1), $prevIndex, 'NoExtraNewlineAfterTags');
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
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockStartIndex
     * @param int $nextIndex
     *
     * @return void
     */
    protected function checkBeginningOfDocBlock(File $phpCsFile, int $docBlockStartIndex, int $nextIndex): void
    {
        $tokens = $phpCsFile->getTokens();

        $diff = $tokens[$nextIndex]['line'] - $tokens[$docBlockStartIndex]['line'];
        if ($diff < 2) {
            return;
        }

        $fix = $phpCsFile->addFixableError('Expected no extra blank line before tags, got ' . ($diff - 1), $nextIndex, 'NoExtraNewlineBeforeTags');
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
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return void
     */
    protected function checkAnnotationTagGrouping(File $phpCsFile, int $docBlockStartIndex, int $docBlockEndIndex): void
    {
        $tags = $this->readTags($phpCsFile, $docBlockStartIndex, $docBlockEndIndex);

        $currentTag = null;
        foreach ($tags as $i => $tag) {
            if ($currentTag === null) {
                $currentTag = $tag['tag'];

                continue;
            }

            if ($currentTag === $tag['tag'] || strpos($tag['tag'], $currentTag) === 0) {
                $this->assertNoSpacing($phpCsFile, $tags[$i - 1], $tag);

                continue;
            }

            $this->assertSpacing($phpCsFile, $tags[$i - 1], $tag);
            $currentTag = $tag['tag'];
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return array
     */
    protected function readTags(File $phpCsFile, int $docBlockStartIndex, int $docBlockEndIndex): array
    {
        $tokens = $phpCsFile->getTokens();

        $tags = [];

        for ($i = $docBlockStartIndex; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['code'] !== T_DOC_COMMENT_TAG) {
                continue;
            }

            $start = $this->getFirstTokenOfLine($tokens, $i);
            $end = $this->getEndIndex($tokens, $i);
            $tagEnd = $this->getTagEndIndex($tokens, $start, $end);

            $tag = [
                'index' => $i,
                'tag' => $tokens[$i]['content'],
                'tagEnd' => $tagEnd,
                'start' => $start,
                'end' => $end,
                'content' => $this->getContent($tokens, $i, $tagEnd),
            ];
            $tags[] = $tag;
            $i = $end;
        }

        return $tags;
    }

    /**
     * @param array $tokens
     * @param int $index
     *
     * @return int
     */
    protected function getEndIndex(array $tokens, int $index): int
    {
        $startIndex = $index;
        while (!empty($tokens[$index + 1]) && $tokens[$index + 1]['code'] !== T_DOC_COMMENT_CLOSE_TAG && $tokens[$index + 1]['code'] !== T_DOC_COMMENT_TAG) {
            $index++;
        }

        // Jump to the previous line
        $currentLine = $tokens[$index]['line'];
        while ($tokens[$index]['line'] === $currentLine) {
            $index--;
        }
        // Fix for single line doc blocks
        $index = max($index, $startIndex);

        return $this->getLastTokenOfLine($tokens, $index);
    }

    /**
     * @param array $tokens
     * @param int $start
     * @param int $end
     *
     * @return int
     */
    protected function getTagEndIndex(array $tokens, int $start, int $end): int
    {
        for ($i = $end; $i > $start; $i--) {
            if ($tokens[$i]['code'] !== T_DOC_COMMENT_STRING) {
                continue;
            }

            return $i;
        }

        return $start;
    }

    /**
     * @param array $tokens
     * @param int $start
     * @param int $end
     *
     * @return string
     */
    protected function getContent(array $tokens, int $start, int $end): string
    {
        $content = '';
        for ($i = $start; $i <= $end; $i++) {
            $content .= $tokens[$i]['content'];
        }

        return $content;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param array $first
     * @param array $second
     *
     * @return void
     */
    protected function assertNoSpacing(File $phpCsFile, array $first, array $second): void
    {
        $tokens = $phpCsFile->getTokens();

        $lastIndexOfFirst = $first['tagEnd'];
        $lastLineOfFirst = $tokens[$lastIndexOfFirst]['line'];

        $tagIndexOfSecond = $second['index'];
        $firstLineOfSecond = $tokens[$tagIndexOfSecond]['line'];

        if ($lastLineOfFirst === $firstLineOfSecond - 1) {
            return;
        }

        $fix = $phpCsFile->addFixableError('No newline expected between tags of the same type `' . $first['tag'] . '`', $tagIndexOfSecond, 'NoNewlineBetweenSameType');
        if (!$fix) {
            return;
        }

        $phpCsFile->fixer->beginChangeset();

        for ($i = $first['tagEnd'] + 1; $i < $second['start']; $i++) {
            if ($tokens[$i]['line'] <= $lastLineOfFirst || $tokens[$i]['line'] >= $firstLineOfSecond) {
                continue;
            }

            $phpCsFile->fixer->replaceToken($i, '');
        }

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param array $first
     * @param array $second
     *
     * @return void
     */
    protected function assertSpacing(File $phpCsFile, array $first, array $second): void
    {
        $tokens = $phpCsFile->getTokens();

        $lastIndexOfFirst = $first['tagEnd'];
        $lastLineOfFirst = $tokens[$lastIndexOfFirst]['line'];

        $tagIndexOfSecond = $second['index'];
        $firstLineOfSecond = $tokens[$tagIndexOfSecond]['line'];

        if ($lastLineOfFirst === $firstLineOfSecond - 2) {
            return;
        }

        $error = 'A single newline expected between tags of different types `' . $first['tag'] . '`/`' . $second['tag'] . '`';
        $fix = $phpCsFile->addFixableError($error, $tagIndexOfSecond, 'NewlineBetweenDifferentTypes');
        if (!$fix) {
            return;
        }

        if ($lastLineOfFirst > $firstLineOfSecond - 2) {
            $phpCsFile->fixer->beginChangeset();

            $indentation = $this->getIndentationWhitespace($phpCsFile, $tagIndexOfSecond);
            $phpCsFile->fixer->addNewlineBefore($second['start']);
            $phpCsFile->fixer->addContentBefore($second['start'], $indentation . '*');

            $phpCsFile->fixer->endChangeset();

            return;
        }

        $phpCsFile->fixer->beginChangeset();

        for ($i = $first['tagEnd'] + 1; $i < $second['start']; $i++) {
            if ($tokens[$i]['line'] <= $firstLineOfSecond - 2) {
                continue;
            }

            $phpCsFile->fixer->replaceToken($i, '');
        }

        $phpCsFile->fixer->endChangeset();
    }
}
