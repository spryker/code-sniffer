<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Sniffs\Plugin;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractMethodAnnotationSniff;

abstract class AbstractPluginMethodAnnotationSniff extends AbstractMethodAnnotationSniff
{
    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isPlugin(File $phpCsFile, int $stackPointer): bool
    {
        if ($this->isFileInPluginDirectory($phpCsFile) && $this->extendsAbstractPlugin($phpCsFile, $stackPointer)) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return int
     */
    protected function isFileInPluginDirectory(File $phpCsFile): int
    {
        return preg_match('/Communication\/Plugin/', $phpCsFile->getFilename());
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function extendsAbstractPlugin(File $phpCsFile, int $stackPointer): bool
    {
        $extendedClassName = $phpCsFile->findExtendedClassName($stackPointer);

        // We do not force-annotate on abstract classes
        $abstractClassTypeIndex = $phpCsFile->findPrevious(T_ABSTRACT, $stackPointer - 1);
        if ($abstractClassTypeIndex !== false) {
            return false;
        }

        if ($extendedClassName === 'AbstractPlugin') {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return int
     */
    protected function getStackPointerOfClassBegin(File $phpCsFile, int $stackPointer): int
    {
        $abstractPosition = $phpCsFile->findPrevious(T_ABSTRACT, $stackPointer);
        if ($abstractPosition) {
            return $abstractPosition;
        }

        return $stackPointer;
    }
}
