<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Type;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractMethodAnnotationSniff;

abstract class AbstractTypeMethodAnnotationSniff extends AbstractMethodAnnotationSniff
{
    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isType(File $phpCsFile, int $stackPointer): bool
    {
        return $this->extendsAbstractType($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function extendsAbstractType(File $phpCsFile, int $stackPointer): bool
    {
        $extendedClassName = $phpCsFile->findExtendedClassName($stackPointer);

        // We do not force-annotate on abstract classes
        $abstractClassTypeIndex = $phpCsFile->findPrevious(T_ABSTRACT, $stackPointer - 1);
        if ($abstractClassTypeIndex !== false) {
            return false;
        }

        if ($extendedClassName === 'AbstractType') {
            return true;
        }

        return false;
    }
}
