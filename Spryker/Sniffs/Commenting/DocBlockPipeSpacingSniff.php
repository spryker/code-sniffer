<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * No spaces around pipes in doc block hints.
 */
class DocBlockPipeSpacingSniff implements Sniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_DOC_COMMENT_STRING,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $content = $tokens[$stackPtr]['content'];

        $description = '';
        $hint = $content;
        if (strpos($hint, ' ') !== false) {
            [$hint, $description] = explode(' ', $content, 2);
        }

        // Bugfix for https://github.com/squizlabs/PHP_CodeSniffer/issues/2613
        $trailingWhitespace = '';
        if (!$description && $this->isInlineDocBlock($phpcsFile, $stackPtr) && preg_match('#(\s+)$#', $content, $matches)) {
            $trailingWhitespace = $matches[1];
        }

        if (strpos($hint, '|') === false) {
            return;
        }

        $pieces = explode('|', $hint);

        $hints = [];
        foreach ($pieces as $piece) {
            $hints[] = trim($piece);
        }

        $desc = ltrim($description);

        while (!empty($desc) && mb_substr($desc, 0, 1) === '|') {
            $desc = ltrim(mb_substr($desc, 1));

            $pos = mb_strpos($desc, ' ');
            if ($pos > 0) {
                $hints[] = trim(mb_substr($desc, 0, $pos));
                $desc = ltrim(mb_substr($desc, $pos));
            } else {
                $hints[] = $desc;
                $desc = '';
            }
        }

        if ($desc !== '') {
            $desc = ' ' . $desc;
        }

        $newContent = implode('|', $hints) . $desc . $trailingWhitespace;

        if ($newContent !== $content) {
            $fix = $phpcsFile->addFixableError('There should be no space around pipes in doc blocks.', $stackPtr, 'InvalidSpaceAroundPipes');
            if ($fix) {
                $phpcsFile->fixer->replaceToken($stackPtr, $newContent);
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return bool
     */
    protected function isInlineDocBlock(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();

        $closeIndex = $phpcsFile->findNext(T_DOC_COMMENT_CLOSE_TAG, $stackPtr + 1);
        if (!$closeIndex) {
            return false;
        }

        $line = $tokens[$stackPtr]['line'];
        $closingLine = $tokens[$closeIndex]['line'];

        return $line === $closingLine;
    }
}
