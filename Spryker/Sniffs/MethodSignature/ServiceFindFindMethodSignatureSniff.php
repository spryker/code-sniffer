<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Sniffs\MethodSignature;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractFindMethodSignatureSniff;

class ServiceFindFindMethodSignatureSniff extends AbstractFindMethodSignatureSniff
{
    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isSniffApplicable(File $phpCsFile, int $stackPointer): bool
    {
        return $this->isService($phpCsFile, $stackPointer);
    }
}
