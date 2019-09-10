<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Tools\Traits\CommentingTrait;
use Spryker\Tools\Traits\SignatureTrait;

/**
 * Doc blocks should always be normalized.
 */
class DocBlockStructureSniff extends AbstractSprykerSniff
{
    use CommentingTrait;
    use SignatureTrait;

    /**
     * @inheritDoc
     */
    public function register(): array
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

        $closingTagIndex = $tokens[$stackPtr]['comment_closer'];

        if ($tokens[$closingTagIndex]['line'] === $tokens[$stackPtr]['line']) {
            return;
        }

        $nextIndex = $phpcsFile->findNext(T_DOC_COMMENT_WHITESPACE, $stackPtr + 1, $closingTagIndex, true);
        if (!$nextIndex) {
            return;
        }

        if ($tokens[$nextIndex]['type'] === 'T_DOC_COMMENT_STAR') {
            return;
        }

        $fix = $phpcsFile->addFixableError('Doc block beginning invalid.', $stackPtr, 'Invalid');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();

        $level = $tokens[$stackPtr]['level'];

        $index = $nextIndex;
        while ($index > $stackPtr + 1) {
            $index--;
            $phpcsFile->fixer->replaceToken($index, '');
        }

        $phpcsFile->fixer->addNewline($stackPtr);
        $phpcsFile->fixer->addContent($stackPtr, str_repeat(' ', $level * 4));
        $phpcsFile->fixer->addContent($stackPtr, ' * ');

        $phpcsFile->fixer->endChangeset();
    }
}
