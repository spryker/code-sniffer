<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

class StringOnlyInConstantSniff extends AbstractSprykerSniff
{
    /**
     * @inheritdoc
     */
    public function register()
    {
        return [
            T_CONSTANT_ENCAPSED_STRING,
        ];
    }

    /**
     * @inheritdoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $decName = $phpcsFile->findNext(T_CONSTANT_ENCAPSED_STRING, $stackPtr);
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$decName]['level'] === 1) {
            return;
        }

        $error = '%s string should be introduced as a class or module constant.';
        $data = [
            $tokens[$stackPtr]['content']
        ];
        $phpcsFile->addError($error, $stackPtr, 'NoMatch', $data);
    }
}
