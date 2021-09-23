<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Checks if inline doc blocks have the correct order and format.
 */
class InlineDocBlockSniff extends AbstractSprykerSniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_FUNCTION,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        $tokens = $phpCsFile->getTokens();
        $startIndex = $phpCsFile->findNext(T_OPEN_CURLY_BRACKET, $stackPointer + 1);
        if (!$startIndex || empty($tokens[$startIndex]['bracket_closer'])) {
            return;
        }

        $endIndex = $tokens[$startIndex]['bracket_closer'];

        $this->fixDocCommentOpenTags($phpCsFile, $startIndex, $endIndex);

        $this->checkInlineComments($phpCsFile, $startIndex, $endIndex);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $startIndex
     * @param int $endIndex
     *
     * @return void
     */
    protected function fixDocCommentOpenTags(File $phpCsFile, int $startIndex, int $endIndex): void
    {
        $tokens = $phpCsFile->getTokens();

        for ($i = $startIndex + 1; $i < $endIndex; $i++) {
            if ($tokens[$i]['code'] !== T_COMMENT) {
                continue;
            }

            if (!preg_match('|^\/\*\s*@\w+ (.+)|', $tokens[$i]['content'])) {
                continue;
            }

            $fix = $phpCsFile->addFixableError('Inline Doc Block comment should be using `/** ... */`', $i, 'InlineDocBlock');
            if ($fix) {
                $phpCsFile->fixer->beginChangeset();

                $comment = $tokens[$i]['content'];
                $comment = str_replace('/*', '/**', $comment);

                $phpCsFile->fixer->replaceToken($i, $comment);

                $phpCsFile->fixer->endChangeset();
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $index
     *
     * @return void
     */
    protected function fixDocCommentCloseTags(File $phpCsFile, int $index): void
    {
        $tokens = $phpCsFile->getTokens();

        $content = $tokens[$index]['content'];
        if ($content === '*/') {
            return;
        }

        $fix = $phpCsFile->addFixableError('Inline Doc Block comment end tag should be `*/`, got `' . $content . '`', $index, 'EndTag');
        if (!$fix) {
            return;
        }

        $phpCsFile->fixer->beginChangeset();

        $phpCsFile->fixer->replaceToken($index, '*/');

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $startIndex
     * @param int $endIndex
     *
     * @return void
     */
    protected function checkInlineComments(File $phpCsFile, int $startIndex, int $endIndex): void
    {
        $tokens = $phpCsFile->getTokens();

        for ($i = $startIndex + 1; $i < $endIndex; $i++) {
            if ($tokens[$i]['code'] !== T_DOC_COMMENT_OPEN_TAG) {
                continue;
            }

            $commentEndTag = $tokens[$i]['comment_closer'];

            $this->fixDocCommentCloseTags($phpCsFile, $commentEndTag);

            // We skip for multiline for now
            if ($tokens[$i]['line'] !== $tokens[$commentEndTag]['line']) {
                continue;
            }

            $typeTag = $this->findTagIndex($tokens, $i, $commentEndTag, T_DOC_COMMENT_TAG);
            $contentTag = $this->findTagIndex($tokens, $i, $commentEndTag, T_DOC_COMMENT_STRING);

            if ($typeTag === null || $contentTag === null) {
                $phpCsFile->addError('Invalid Inline Doc Block', $startIndex, 'DocBlockInvalid');

                return;
            }

            if ($tokens[$typeTag]['content'] !== '@var') {
                // We ignore those
                return;
            }

            $errors = $this->findErrors($phpCsFile, $contentTag);

            if (!$errors) {
                continue;
            }

            $fix = $phpCsFile->addFixableError('Invalid Inline Doc Block content: ' . implode(', ', $errors), $i, 'DocBlockContentInvalid');
            if (!$fix) {
                continue;
            }

            $phpCsFile->fixer->beginChangeset();

            $comment = $tokens[$contentTag]['content'];

            if (isset($errors['space-before-end']) || isset($errors['end'])) {
                $comment .= ' ';
            }

            if (isset($errors['order'])) {
                $comment = preg_replace('|^(.+?)\s+(.+?)\s*$|', '\2 \1 ', $comment);
            }

            $phpCsFile->fixer->replaceToken($contentTag, $comment);

            $phpCsFile->fixer->endChangeset();
        }
    }

    /**
     * @param array<int, array<string, mixed>> $tokens
     * @param int $from
     * @param int $to
     * @param string $tagType
     *
     * @return int|null
     */
    protected function findTagIndex(array $tokens, int $from, int $to, string $tagType): ?int
    {
        for ($i = $from + 1; $i < $to; $i++) {
            if ($tokens[$i]['code'] === $tagType) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $contentIndex
     *
     * @return array<string>
     */
    protected function findErrors(File $phpCsFile, int $contentIndex): array
    {
        $tokens = $phpCsFile->getTokens();

        $comment = $tokens[$contentIndex]['content'];

        // SKip for complex arrays until next major
        if (strpos($comment, '<') !== false) {
            return [];
        }

        preg_match('#^(.+?)(\s+)(.+?)\s*$#', $comment, $contentMatches);
        if (!$contentMatches || !$contentMatches[1] || !$contentMatches[3]) {
            if ($this->hasReturnAsFollowingToken($phpCsFile, $contentIndex)) {
                return [];
            }

            $phpCsFile->addError('Invalid Inline Doc Block content, expected `{Type} ${var}` style', $contentIndex, 'ContentInvalid');

            return [];
        }

        $errors = [];

        if (!preg_match('|([a-z0-9]) $|i', $comment)) {
            $errors['space-before-end'] = 'Expected single space before ´*/´';
        }

        if (!preg_match('|^\$[a-z0-9]+$|i', $contentMatches[3])) {
            $errors['order'] = 'Expected ´{Type} ${var}´, got `' . $contentMatches[1] . $contentMatches[2] . $contentMatches[3] . '`';
        }

        return $errors;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $contentIndex
     *
     * @return bool
     */
    protected function hasReturnAsFollowingToken(File $phpCsFile, int $contentIndex): bool
    {
        $nextIndex = $phpCsFile->findNext(Tokens::$emptyTokens, $contentIndex + 1, null, true);
        if (!$nextIndex) {
            return false;
        }

        $tokens = $phpCsFile->getTokens();

        return $tokens[$nextIndex]['code'] === T_RETURN;
    }
}
