<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace GlueStreamSpecific\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;

class DependencyProviderStringInConstantOnlySniff extends AbstractStringInConstantOnlySniff
{
    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isRuleApplicable(File $phpCsFile): bool
    {
        $className = $this->getClassName($phpCsFile);
        $hasCorrectSuffix = (substr($className, -18) === 'DependencyProvider');

        return $hasCorrectSuffix;
    }
}
