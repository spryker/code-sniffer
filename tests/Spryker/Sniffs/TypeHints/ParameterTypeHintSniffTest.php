<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\TypeHints;

use Spryker\Test\TestCase;
use SprykerStrict\Sniffs\TypeHints\ParameterTypeHintSniff;

class ParameterTypeHintSniffTest extends TestCase
{
    public function testGivenParamAnnotationsRestatingTheNativeTypeHintWhenSniffedThenOnlyThoseAreReported(): void
    {
        // Arrange
        $sniff = new ParameterTypeHintSniff();

        // Act & Assert
        $this->assertSnifferFindsErrors($sniff, 2);
    }

    public function testGivenParamMixedUnionsCarryingTypeDetailWhenFixedThenOnlyTheRestatedAnnotationsAreRemoved(): void
    {
        // Arrange
        $sniff = new ParameterTypeHintSniff();

        // Act & Assert
        $this->assertSnifferCanFixErrors($sniff, 2);
    }
}
