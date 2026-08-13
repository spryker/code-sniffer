<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerStrict\Sniffs\TypeHints;

use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSniff as SlevomatReturnTypeHintSniff;
use Spryker\Traits\MixedUnionAnnotationTrait;

class ReturnTypeHintSniff extends SlevomatReturnTypeHintSniff
{
    use MixedUnionAnnotationTrait;

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $pointer): void
    {
        if ($this->hasDetailedMixedUnionReturnAnnotation($phpcsFile, $pointer)) {
            return;
        }

        parent::process($phpcsFile, $pointer);
    }
}
