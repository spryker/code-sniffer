<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerStrict\Sniffs\TypeHints;

use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Sniffs\TypeHints\TypeHintDeclarationSniff as SlevomatTypeHintDeclarationSniff;
use Spryker\Traits\BridgeTrait;

class TypeHintDeclarationSniff extends SlevomatTypeHintDeclarationSniff
{
    use BridgeTrait;

    /**
     * @inheritdoc
     */
    public function process(File $phpcsFile, $pointer): void
    {
        if ($this->isSprykerBridgeConstructor($phpcsFile, $pointer)) {
            return;
        }

        parent::process($phpcsFile, $pointer);
    }
}
