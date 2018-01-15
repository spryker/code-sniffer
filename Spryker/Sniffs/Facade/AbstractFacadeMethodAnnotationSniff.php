<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Sniffs\Facade;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractMethodAnnotationSniff;

abstract class AbstractFacadeMethodAnnotationSniff extends AbstractMethodAnnotationSniff
{
    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isFacade(File $phpCsFile)
    {
        $className = $this->getClassName($phpCsFile);
        $bundleName = $this->getModule($phpCsFile);

        $facadeName = $bundleName . 'Facade';
        $stringLength = strlen($facadeName);
        $relevantClassNamePart = substr($className, -$stringLength);

        return ($relevantClassNamePart === $facadeName);
    }
}
