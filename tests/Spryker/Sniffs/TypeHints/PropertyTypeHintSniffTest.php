<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\TypeHints;

use Spryker\Test\TestCase;
use SprykerStrict\Sniffs\TypeHints\PropertyTypeHintSniff;

class PropertyTypeHintSniffTest extends TestCase
{
    public function testGivenVarAnnotationsRestatingTheNativeTypeHintWhenSniffedThenOnlyThoseAreReported(): void
    {
        // Arrange
        $sniff = new PropertyTypeHintSniff();

        // Act & Assert
        $this->assertSnifferFindsErrors($sniff, 2);
    }

    public function testGivenVarMixedUnionsCarryingTypeDetailWhenFixedThenOnlyTheRestatedAnnotationsAreRemoved(): void
    {
        // Arrange
        $sniff = new PropertyTypeHintSniff();

        // Act & Assert
        $this->assertSnifferCanFixErrors($sniff, 2);
    }
}
