<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\TypeHints;

use Spryker\Test\TestCase;
use SprykerStrict\Sniffs\TypeHints\ClassConstantTypeHintSniff;

class ClassConstantTypeHintSniffTest extends TestCase
{
    public function testGivenClassConstantsWithoutNativeTypeHintsWhenSniffedThenErrorsAreReported(): void
    {
        // Arrange
        $sniff = new ClassConstantTypeHintSniff();

        // Act & Assert
        $this->assertSnifferFindsErrors($sniff, 3);
    }
}
