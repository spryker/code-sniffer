<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\TypeHints;

use Spryker\Test\TestCase;
use SprykerStrict\Sniffs\TypeHints\ReturnTypeHintSniff;

class ReturnTypeHintSniffTest extends TestCase
{
    public function testGivenReturnAnnotationsRestatingTheNativeTypeHintWhenSniffedThenOnlyThoseAreReported(): void
    {
        // Arrange
        $sniff = new ReturnTypeHintSniff();

        // Act & Assert
        $this->assertSnifferFindsErrors($sniff, 2);
    }

    public function testGivenReturnMixedUnionsCarryingTypeDetailWhenFixedThenOnlyTheRestatedAnnotationsAreRemoved(): void
    {
        // Arrange
        $sniff = new ReturnTypeHintSniff();

        // Act & Assert
        $this->assertSnifferCanFixErrors($sniff, 2);
    }
}
