<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks that attributes are always `\FQCN`.
 */
class AttributesSniff implements Sniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_ATTRIBUTE,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpCsFile, $stackPointer): void
    {
        $nextIndex = $phpCsFile->findNext(Tokens::$emptyTokens, $stackPointer + 1, null, true);
        if (!$nextIndex) {
            return;
        }

        $tokens = $phpCsFile->getTokens();

        if ($tokens[$nextIndex]['code'] === T_NS_SEPARATOR) {
            return;
        }

        $phpCsFile->addError('FQCN expected for attribute', $nextIndex, 'ExpectedFQCN');
    }
}
