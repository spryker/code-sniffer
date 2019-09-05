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
 * Constructor and destructor must not have noise header lines.
 */
class DocBlockConstructorSniff extends AbstractSprykerSniff
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
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);
        if ($tokens[$nextIndex]['content'] !== '__construct' && $tokens[$nextIndex]['content'] !== '__destruct') {
            return;
        }

        $docBlockEndIndex = $this->findRelatedDocBlock($phpcsFile, $stackPtr);
        if (!$docBlockEndIndex) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        $firstLineIndex = $phpcsFile->findNext(T_DOC_COMMENT_STRING, $docBlockStartIndex, $docBlockEndIndex);
        if ($tokens[$firstLineIndex]['line'] !== $tokens[$docBlockStartIndex]['line'] + 1) {
            return;
        }

        if (!preg_match('/^\w+ (constructor|destructor)\.$/i', $tokens[$firstLineIndex]['content'])) {
            return;
        }

        $fix = $phpcsFile->addFixableError('Doc Block has unneeded header line.', $firstLineIndex, 'UnneededNoise');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();

        $phpcsFile->fixer->replaceToken($firstLineIndex, '');

        $index = $firstLineIndex;
        while ($tokens[$index - 1]['line'] === $tokens[$firstLineIndex]['line']) {
            $index--;
            $phpcsFile->fixer->replaceToken($index, '');
        }
        $index = $firstLineIndex;
        while ($tokens[$index + 1]['line'] === $tokens[$firstLineIndex]['line']) {
            $index++;
            $phpcsFile->fixer->replaceToken($index, '');
        }

        $phpcsFile->fixer->endChangeset();
    }
}
