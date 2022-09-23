<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerStrict\Sniffs\TypeHints;

use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSniff as SlevomatParameterTypeHintSniff;
use Spryker\Traits\BridgeTrait;

class ParameterTypeHintSniff extends SlevomatParameterTypeHintSniff
{
    use BridgeTrait;

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $pointer): void
    {
        if ($this->isSprykerBridgeConstructor($phpcsFile, $pointer)) {
            return;
        }

        parent::process($phpcsFile, $pointer);
    }
}
