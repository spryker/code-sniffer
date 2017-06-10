<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Sniffs\Namespaces;

use PHP_CodeSniffer_File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Traits\UseStatementsTrait;

/**
 * Ensures all use statements have no leading backslash.
 */
class UseWithLeadingBackslashSniff extends AbstractSprykerSniff
{

    use UseStatementsTrait;

    /**
     * @inheritdoc
     */
    public function register()
    {
        return [T_CLASS, T_INTERFACE, T_TRAIT];
    }

    /**
     * @inheritdoc
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $useStatements = $this->getUseStatements($phpcsFile);

        foreach ($useStatements as $useStatement) {
            if (strpos($useStatement['statement'], '\\') !== 0) {
                continue;
            }

            $error = 'Leading backslash is not allowed in use statements.';
            $phpcsFile->addError($error, $useStatement['statement'], 'Backslash');
        }
    }

}
