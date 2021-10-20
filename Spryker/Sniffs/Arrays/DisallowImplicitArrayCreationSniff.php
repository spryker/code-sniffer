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
        $fileName = $phpcsFile->getFilename();
        if ($this->hasLegacyImplicitCreation($fileName)) {
            return;
        }

        parent::process($phpcsFile, $stackPtr);
    }

    /**
     * @param string $fileName
     *
     * @return bool
     */
    protected function hasLegacyImplicitCreation(string $fileName): bool
    {
        if (strpos($fileName, DIRECTORY_SEPARATOR . 'config_') !== false || strpos($fileName, DIRECTORY_SEPARATOR . 'config.') !== false) {
            return true;
        }
        if (strpos($fileName, DIRECTORY_SEPARATOR . 'cronjobs' . DIRECTORY_SEPARATOR) !== false) {
            return true;
        }

        return false;
    }
}
