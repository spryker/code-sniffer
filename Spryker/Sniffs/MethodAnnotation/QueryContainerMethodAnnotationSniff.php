<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\MethodAnnotation;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractMethodAnnotationSniff;

/**
 * Spryker classes should have a getQueryContainer() annotation.
 */
class QueryContainerMethodAnnotationSniff extends AbstractMethodAnnotationSniff
{
    /**
     * @return string
     */
    protected function getMethodName(): string
    {
        return 'getQueryContainer';
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getMethodFileAddedName(File $phpCsFile): string
    {
        return $this->getModule($phpCsFile) . 'QueryContainerInterface';
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function getSnifferIsApplicable(File $phpCsFile, int $stackPointer): bool
    {
        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param string $namespacePart
     *
     * @return string
     */
    protected function getMethodAnnotationFileName(File $phpCsFile, string $namespacePart): string
    {
        $className = $this->getClassName($phpCsFile);
        $classNameParts = explode('\\', $className);
        $classNameParts = array_slice($classNameParts, 0, 3);
        $classNameParts[0] = $namespacePart;
        array_push($classNameParts, static::LAYER_PERSISTENCE);
        array_push($classNameParts, $this->getMethodFileAddedName($phpCsFile));

        return '\\' . implode('\\', $classNameParts);
    }
}
