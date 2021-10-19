<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Arrays;

use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Sniffs\Arrays\DisallowImplicitArrayCreationSniff as SlevomatDisallowImplicitArrayCreationSniff;

/**
 * Customize to exclude config files (non namespaced classes)
 */
class DisallowImplicitArrayCreationSniff extends SlevomatDisallowImplicitArrayCreationSniff
{
    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        // We skip on config files.
        if (strpos($phpcsFile->getFilename(), DIRECTORY_SEPARATOR . 'config_') !== false) {
            return;
        }

        parent::process($phpcsFile, $stackPtr);
    }
}
