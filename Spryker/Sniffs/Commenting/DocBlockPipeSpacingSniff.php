<?php
/**
 * (c) Spryker Systems GmbH copyright protected.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Sniff;

/**
 * No spaces around pipes in doc block hints.
 */
class DocBlockPipeSpacingSniff implements PHP_CodeSniffer_Sniff
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
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
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
            $fix = $phpcsFile->addFixableError('There should be no space around pipes in doc blocks.', $stackPtr);
            if ($fix) {
                $phpcsFile->fixer->replaceToken($stackPtr, $newContent);
            }
        }
    }

}
