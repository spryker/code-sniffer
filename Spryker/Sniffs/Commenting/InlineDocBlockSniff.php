<?php

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer_File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Checks if inline doc blocks have the correct order and format.
 */
class InlineDocBlockSniff extends AbstractSprykerSniff
{

    /**
     * @inheritdoc
     */
    public function register()
    {
        return [
            T_FUNCTION,
        ];
    }

    /**
     * @inheritdoc
     */
    public function process(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $tokens = $phpCsFile->getTokens();
        $startIndex = $phpCsFile->findNext(T_OPEN_CURLY_BRACKET, $stackPointer + 1);
        if (empty($tokens[$startIndex]['bracket_closer'])) {
            return;
        }

        $endIndex = $tokens[$startIndex]['bracket_closer'];

        $this->fixDocCommentOpenTags($phpCsFile, $startIndex, $endIndex);

        $this->checkInlineComments($phpCsFile, $startIndex, $endIndex);
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $startIndex
     * @param int $endIndex
     *
     * @return void
     */
    protected function fixDocCommentOpenTags(PHP_CodeSniffer_File $phpCsFile, $startIndex, $endIndex)
    {
        $tokens = $phpCsFile->getTokens();

        for ($i = $startIndex + 1; $i < $endIndex; $i++) {
            if ($tokens[$i]['code'] !== T_DOC_COMMENT_OPEN_TAG) {
                continue;
            }

            $fix = $phpCsFile->addFixableError('Inline Doc Block Comment should be using /* ... */', $i);
            if ($fix) {
                $phpCsFile->fixer->beginChangeset();

                $phpCsFile->fixer->replaceToken($i, '/*');

                $phpCsFile->fixer->endChangeset();
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $startIndex
     * @param int $endIndex
     *
     * @return void
     */
    protected function checkInlineComments(PHP_CodeSniffer_File $phpCsFile, $startIndex, $endIndex)
    {
        $tokens = $phpCsFile->getTokens();

        for ($i = $startIndex + 1; $i < $endIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_COMMENT') {
                continue;
            }

            $comment = $tokens[$i]['content'];
            if (!preg_match('|^/\*(\s*)@var|', $comment, $matches)) {
                continue;
            }

            preg_match('|^\/\*\s*@var\s+(.+?)(\s+)(.+?)\s*\*\/$|', $comment, $contentMatches);
            if (!$contentMatches || !$contentMatches[1] || !$contentMatches[3]) {
                $phpCsFile->addError('Invalid Inline Doc Block content', $i);
                continue;
            }

            $errors = [];
            if ($matches[1] !== ' ') {
                $errors['space-before-tag'] = 'Expected single space before ´@var´, got ´' . $matches[1] . '´';
            }

            if (!preg_match('|([a-z0-9])+\s*\*\/$|i', $comment)) {
                $errors['end'] = 'Expected ´ */´ to terminate comment';
            }

            if (!preg_match('|([a-z0-9]) [\*]+\/$|i', $comment)) {
                $errors['space-before-end'] = 'Expected single space before ´*/´';
            }

            if ($contentMatches[2] !== ' ') {
                $errors['space-between-type-and-var'] = 'Expected a single space between type and var, got `' . $contentMatches[2] . '`';
            }

            if (!preg_match('|^\$[a-z0-9]+$|i', $contentMatches[3])) {
                $errors['order'] = 'Expected ´{Type} ${var}´, got `' . $contentMatches[1] . $contentMatches[2] . $contentMatches[3] . '`';
            }

            if (!$errors) {
                continue;
            }

            $fix = $phpCsFile->addFixableError('Invalid Inline Doc Block: ' . implode(', ', $errors), $i);
            if (!$fix) {
                continue;
            }

            $phpCsFile->fixer->beginChangeset();

            if (isset($errors['space-before-tag'])) {
                $comment = preg_replace('|^/\*(\s*)@var|', '/* @var', $comment);
            }
            if (isset($errors['space-before-end']) || isset($errors['end'])) {
                $comment = preg_replace('|\b\s*[\*]+\/$|i', ' */', $comment);
            }
            if (isset($errors['space-between-type-and-var'])) {
                $comment = preg_replace('|@var\s+(.+?)\s+|', '@var \1 ', $comment);
            }
            if (isset($errors['order'])) {
                $comment = preg_replace('|@var\s+(.+?)\s+(.+?)\s+|', '@var \2 \1 ', $comment);
            }

            $phpCsFile->fixer->replaceToken($i, $comment);

            $phpCsFile->fixer->endChangeset();
        }
    }

}
