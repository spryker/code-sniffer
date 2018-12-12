<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

class DocBlockReturnNullableTypeSniff extends AbstractSprykerSniff
{
    /**
     * @see Tokens.php
     *
     * @return int[]
     */
    public function register(): array
    {
        return [
            T_FUNCTION,
        ];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    public function process(File $phpCsFile, $stackPointer): void
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
