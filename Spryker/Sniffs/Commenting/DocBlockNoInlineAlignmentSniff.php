<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * No inline alignment or additional whitespace for phpdoc tags.
 */
class DocBlockNoInlineAlignmentSniff extends AbstractSprykerSniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_DOC_COMMENT_TAG,
            T_DOC_COMMENT_STRING,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] === T_DOC_COMMENT_STRING) {
            $this->checkDescription($phpcsFile, $stackPtr);
        }
        if ($tokens[$stackPtr]['code'] === T_DOC_COMMENT_TAG) {
            $this->checkTag($phpcsFile, $stackPtr);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkTag(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $followingWhitespace = (int)$phpcsFile->findNext(T_DOC_COMMENT_WHITESPACE, $stackPtr + 1, $stackPtr + 2);
        if (!$followingWhitespace || $tokens[$followingWhitespace]['line'] !== $tokens[$stackPtr]['line']) {
            return;
        }

        // Skip for file doc blocks
        $namespaceStatement = $this->getNamespaceStatement($phpcsFile);
        if (!$namespaceStatement || $stackPtr < $namespaceStatement['start']) {
            return;
        }

        $content = $tokens[$followingWhitespace]['content'];
        if (strpos($content, ' ') === false || $content === ' ') {
            return;
        }

        $fix = $phpcsFile->addFixableError('There should be no additional whitespace around doc block tag types.', $stackPtr, 'WhitespaceAroundTypes');
        if ($fix) {
            $phpcsFile->fixer->replaceToken($followingWhitespace, ' ');
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkDescription(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $content = $tokens[$stackPtr]['content'];

        if (!preg_match('/\s\s+/', $content)) {
            return;
        }

        $fix = $phpcsFile->addFixableError('There should be no inline alignment in doc blocks descriptions.', $stackPtr, 'DocBlockInlineAlignment');
        if ($fix) {
            $newContent = preg_replace('/\s\s+/', ' ', $content);
            $phpcsFile->fixer->replaceToken($stackPtr, $newContent);
        }
    }
}
