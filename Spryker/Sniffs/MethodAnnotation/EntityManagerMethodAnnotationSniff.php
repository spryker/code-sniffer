<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\MethodAnnotation;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractMethodAnnotationSniff;

/**
 * Spryker Facade classes should have a getEntityManager() annotation.
 */
class EntityManagerMethodAnnotationSniff extends AbstractMethodAnnotationSniff
{
    /**
     * @return string
     */
    protected function getMethodName(): string
    {
        return 'getEntityManager';
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getMethodFileAddedName(File $phpCsFile): string
    {
        return $this->getModule($phpCsFile) . 'EntityManagerInterface';
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function getSnifferIsApplicable(File $phpCsFile, int $stackPointer): bool
    {
        if ($this->isFacade($phpCsFile, $stackPointer)) {
            return true;
        }

        if ($this->isFactory($phpCsFile, $stackPointer)) {
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
        array_push($classNameParts, static::LAYER_PERSISTENCE);
        array_push($classNameParts, $this->getMethodFileAddedName($phpCsFile));

        return '\\' . implode('\\', $classNameParts);
    }
}
