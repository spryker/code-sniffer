<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\MethodAnnotation;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractMethodAnnotationSniff;

/**
 * Spryker classes should have a getFacade() annotation.
 */
class FacadeMethodAnnotationSniff extends AbstractMethodAnnotationSniff
{
    /**
     * @return string
     */
    protected function getMethodName(): string
    {
        return 'getFacade';
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getMethodFileAddedName(File $phpCsFile): string
    {
        return $this->getModule($phpCsFile) . 'FacadeInterface';
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function getSnifferIsApplicable(File $phpCsFile, int $stackPointer): bool
    {
        if ($this->isController($phpCsFile, $stackPointer)) {
            return true;
        }

        if ($this->isCollectionType($phpCsFile, $stackPointer)) {
            return true;
        }

        if ($this->isConsole($phpCsFile, $stackPointer)) {
            return true;
        }

        if ($this->isCommunicationFactory($phpCsFile, $stackPointer)) {
            return true;
        }

        if ($this->isPlugin($phpCsFile, $stackPointer)) {
            return true;
        }

        if ($this->isType($phpCsFile, $stackPointer)) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getMethodAnnotationFileName(File $phpCsFile): string
    {
        $className = $this->getClassName($phpCsFile);
        $classNameParts = explode('\\', $className);
        $classNameParts = array_slice($classNameParts, 0, 3);
        array_push($classNameParts, static::LAYER_BUSINESS);
        array_push($classNameParts, $this->getMethodFileAddedName($phpCsFile));

        return '\\' . implode('\\', $classNameParts);
    }
}
