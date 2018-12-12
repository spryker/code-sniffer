<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

class DocBlockReturnNullableTypeSniff extends AbstractSprykerSniff
{
    /**
     * @return int[]
     * @see    Tokens.php
     */
    public function register(): array
    {
        return [
            T_FUNCTION
        ];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return int|void
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        $returnType = FunctionHelper::findReturnTypeHint($phpCsFile, $stackPointer);

        if ($returnType === null) {
            return;
        }

        if (!$returnType->isNullable()) {
            return;
        }

        $docBlockReturnTypes = $this->getDocBlockReturnTypes($phpCsFile, $stackPointer);

        if (in_array('null', $docBlockReturnTypes)) {
            return;
        }

        $errorMessage = 'Method does not have a return `nullable` statement in doc block.';
        $phpCsFile->addError($errorMessage, $stackPointer, 'ReturnNullableMissing');
    }
}
