<?php

namespace Spryker\Sniffs\Facade;

use PHP_CodeSniffer_File;
use Spryker\Sniffs\AbstractSniffs\AbstractMethodAnnotationSniff;

abstract class AbstractFacadeMethodAnnotationSniff extends AbstractMethodAnnotationSniff
{

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     *
     * @return bool
     */
    protected function isFacade(PHP_CodeSniffer_File $phpCsFile)
    {
        $className = $this->getClassName($phpCsFile);
        $bundleName = $this->getBundle($phpCsFile);

        $facadeName = $bundleName . 'Facade';
        $stringLength = strlen($facadeName);
        $relevantClassNamePart = substr($className, -$stringLength);

        return ($relevantClassNamePart === $facadeName);
    }

}
