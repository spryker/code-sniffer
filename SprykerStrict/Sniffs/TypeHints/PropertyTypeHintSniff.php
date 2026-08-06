<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerStrict\Sniffs\TypeHints;

use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Sniffs\TypeHints\PropertyTypeHintSniff as SlevomatPropertyTypeHintSniff;
use Spryker\Traits\MixedUnionAnnotationTrait;

class PropertyTypeHintSniff extends SlevomatPropertyTypeHintSniff
{
    use MixedUnionAnnotationTrait;

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $pointer): void
    {
        if ($this->hasDetailedMixedUnionVarAnnotation($phpcsFile, $pointer)) {
            return;
        }

        parent::process($phpcsFile, $pointer);
    }
}
