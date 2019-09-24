<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * No empty phpdoc blocks and no empty @ tag.
 */
class DocBlockNoEmptySniff extends AbstractSprykerSniff
{
    /**
     * @inheritDoc
     */
    public function register()
    {
        return [
            T_DOC_COMMENT_OPEN_TAG,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (empty($tokens[$stackPtr]['comment_closer'])) {
            return;
        }

        $endIndex = $tokens[$stackPtr]['comment_closer'];

        $this->assertNonEmptyDocBlock($phpcsFile, $stackPtr, $endIndex);
        $this->assertNoEmptyTag($phpcsFile, $stackPtr, $endIndex);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     * @param int $endIndex
     *
     * @return void
     */
    protected function assertNonEmptyDocBlock(File $phpcsFile, int $stackPtr, int $endIndex): void
    {
        $nextIndex = $phpcsFile->findNext([T_WHITESPACE, T_DOC_COMMENT_WHITESPACE, T_DOC_COMMENT_STAR], $stackPtr + 1, $endIndex - 1, true);
        if ($nextIndex) {
            return;
        }

        $fix = $phpcsFile->addFixableError('There should be no empty docblocks.', $stackPtr, 'Superfluous');
        if ($fix) {
            for ($i = $stackPtr; $i <= $endIndex; $i++) {
                $phpcsFile->fixer->replaceToken($i, '');
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     * @param int $endIndex
     *
     * @return void
     */
    protected function assertNoEmptyTag(File $phpcsFile, int $stackPtr, int $endIndex): void
    {
        $tokens = $phpcsFile->getTokens();

        $index = $stackPtr;
        while ($index < $endIndex) {
            $nextIndex = $phpcsFile->findNext([T_DOC_COMMENT_STRING], $index + 1, $endIndex);
            if (!$nextIndex) {
                return;
            }
            $index = $nextIndex;

            if (empty($tokens[$index]['content']) || $tokens[$index]['content'] !== '@') {
                continue;
            }

            $fix = $phpcsFile->addFixableError('Empty Doc Block Tag', $index, 'Empty');
            if (!$fix) {
                continue;
            }

            $phpcsFile->fixer->replaceToken($index, '');
        }
    }
}
