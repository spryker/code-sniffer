<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
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
    public function process(File $phpcsFile, $stackPtr)
    {
        $useStatements = $this->getUseStatements($phpcsFile);

        foreach ($useStatements as $useStatement) {
            if (strpos($useStatement['statement'], '\\') !== 0) {
                continue;
            }

            $error = 'Leading backslash is not allowed in use statements: ' . $useStatement['statement'];
            $fixable = $phpcsFile->addFixableError($error, $useStatement['start'], 'Backslash');
            if (!$fixable) {
                return;
            }

            $tokens = $phpcsFile->getTokens();

            for ($i = $useStatement['start'] + 1; $i < $useStatement['end']; $i++) {
                if ($tokens[$i]['code'] !== T_NS_SEPARATOR) {
                    continue;
                }

                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->replaceToken($i, '');
                $phpcsFile->fixer->endChangeset();
                break;
            }
        }
    }
}
