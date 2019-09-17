<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * No invalid tags used.
 */
class DocBlockTagSniff implements Sniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_DOC_COMMENT_TAG,
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

        if (!preg_match('#^@[a-z]+$#i', $hint)) {
            $phpcsFile->addError('Invalid tag `' . $hint . '`', $stackPtr, 'Invalid');
        }
    }
}
