<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Factory;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractMethodAnnotationSniff;

abstract class AbstractFactoryMethodAnnotationSniff extends AbstractMethodAnnotationSniff
{
    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isFactory(File $phpCsFile): bool
    {
        if ($this->isBusinessFactory($phpCsFile)) {
            return true;
        }

        if ($this->isCommunicationFactory($phpCsFile)) {
            return true;
        }

        if ($this->isPersistenceFactory($phpCsFile)) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isBusinessFactory(File $phpCsFile): bool
    {
        return substr($this->getClassName($phpCsFile), -15) === 'BusinessFactory';
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isCommunicationFactory(File $phpCsFile): bool
    {
        return substr($this->getClassName($phpCsFile), -20) === 'CommunicationFactory';
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isPersistenceFactory(File $phpCsFile): bool
    {
        return substr($this->getClassName($phpCsFile), -18) === 'PersistenceFactory';
    }
}
