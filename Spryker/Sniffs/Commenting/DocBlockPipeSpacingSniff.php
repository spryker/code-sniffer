<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
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
     * @inheritdoc
     */
    public function register()
    {
        return [
            T_DOC_COMMENT_STRING,
        ];
    }

    /**
     * @inheritdoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $content = $tokens[$stackPtr]['content'];

        $description = '';
        $hint = $content;
        if (strpos($hint, ' ') !== false) {
            list($hint, $description) = explode(' ', $content, 2);
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

        $newContent = implode('|', $hints) . $desc;

        if ($newContent !== $content) {
            $fix = $phpcsFile->addFixableError('There should be no space around pipes in doc blocks.', $stackPtr, 'InvalidSpaceAroundPipes');
            if ($fix) {
                $phpcsFile->fixer->replaceToken($stackPtr, $newContent);
            }
        }
    }
}
