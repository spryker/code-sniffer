<?php

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Tools\Traits\CommentingTrait;

/**
 * Method doc blocks should have a consistent order of tag types.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockTagOrderSniff extends AbstractSprykerSniff
{

    use CommentingTrait;

    /**
     * All other tags will go above those
     *
     * @var array
     */
    protected $order = [
        '@deprecated',
        '@param',
        '@throws',
        '@return',
    ];

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
    public function process(PHP_CodeSniffer_File $phpCsFile, $stackPtr)
    {
        $tokens = $phpCsFile->getTokens();

        // Don't mess with closures
        $prevIndex = $phpCsFile->findPrevious(PHP_CodeSniffer_Tokens::$emptyTokens, $stackPtr - 1, null, true);
        if (!$this->isGivenKind(PHP_CodeSniffer_Tokens::$methodPrefixes, $tokens[$prevIndex])) {
            return;
        }

        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPtr);
        if (!$docBlockEndIndex) {
            return;
        }

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        $hasInheritDoc = $this->hasInheritDoc($phpCsFile, $docBlockStartIndex, $docBlockEndIndex);
        if ($hasInheritDoc) {
            return;
        }

        $tags = $this->readTags($phpCsFile, $docBlockStartIndex, $docBlockEndIndex);
        $tags = $this->checkAnnotationTagOrder($tags);

        $this->fixOrder($phpCsFile, $docBlockStartIndex, $docBlockEndIndex, $tags);
    }

    /**
     * @param array $tags
     *
     * @return array
     */
    protected function checkAnnotationTagOrder(array $tags)
    {
        $order = $this->getTagOrderMap();

        $currentOrder = null;
        foreach ($tags as $i => $tag) {
            if (!isset($order[$tag['tag']])) {
                if ($currentOrder !== null) {
                    $tags[$i]['error'] = 'Position of ' . $tag['tag'] . ' tag too low.';
                    return $tags;
                }
                continue;
            }

            $tagOrder = $order[$tag['tag']];
            if ($currentOrder === null || $tagOrder >= $currentOrder) {
                $currentOrder = $tagOrder;
                continue;
            }

            $tags[$i]['error'] = 'Position of ' . $tag['tag'] . ' tag too low.';
            continue;
        }

        return $tags;
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return array
     */
    protected function readTags(PHP_CodeSniffer_File $phpCsFile, $docBlockStartIndex, $docBlockEndIndex)
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
    protected function getEndIndex(array $tokens, $index)
    {
        while (!empty($tokens[$index + 1]) && $tokens[$index + 1]['code'] !== T_DOC_COMMENT_CLOSE_TAG && $tokens[$index + 1]['code'] !== T_DOC_COMMENT_TAG) {
            $index++;
        }

        // Jump to the previous line
        $currentLine = $tokens[$index]['line'];
        while ($tokens[$index]['line'] === $currentLine) {
            $index--;
        }

        return $this->getLastTokenOfLine($tokens, $index);
    }

    /**
     * @param array $tokens
     * @param int $start
     * @param int $end
     *
     * @return int
     */
    protected function getTagEndIndex(array $tokens, $start, $end)
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
    protected function getContent(array $tokens, $start, $end)
    {
        $content = '';
        for ($i = $start; $i <= $end; $i++) {
            $content .= $tokens[$i]['content'];
        }

        return $content;
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     * @param array $tags
     *
     * @return void
     */
    protected function fixOrder(PHP_CodeSniffer_File $phpCsFile, $docBlockStartIndex, $docBlockEndIndex, array $tags)
    {
        $errors = [];
        foreach ($tags as $i => $tag) {
            if (isset($tag['error'])) {
                $errors[$i] = $tag['error'];
            }
        }

        if (!$errors) {
            return;
        }

        $fix = $phpCsFile->addFixableError('Invalid order of tags: ' . implode(', ', $errors), $docBlockEndIndex);
        if (!$fix) {
            return;
        }

        $tokens = $phpCsFile->getTokens();

        $phpCsFile->fixer->beginChangeset();

        $order = $this->getTagOrderMap();

        $newOrder = [];
        foreach ($tags as $tag) {
            $tagOrder = isset($order[$tag['tag']]) ? $order[$tag['tag']] : -1;
            $newOrder[$tagOrder][] = $this->getContent($tokens, $tag['start'], $tag['end']);
        }

        ksort($newOrder);
        if (isset($newOrder[-1])) {
            ksort($newOrder[-1]);
        }

        $content = '';
        foreach ($newOrder as $tagGroup) {
            $content .= implode('', $tagGroup);
        }

        $firstTagTokenIndex = $tags[0]['start'];
        $lastTagTokenIndex = $tags[count($tags) - 1]['end'];

        for ($i = $firstTagTokenIndex; $i < $lastTagTokenIndex; $i++) {
            $phpCsFile->fixer->replaceToken($i, '');
        }

        $phpCsFile->fixer->replaceToken($lastTagTokenIndex, $content);

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @return array
     */
    protected function getTagOrderMap()
    {
        return array_flip($this->order);
    }

}
