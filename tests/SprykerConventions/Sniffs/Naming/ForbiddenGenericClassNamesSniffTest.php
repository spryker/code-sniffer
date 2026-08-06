<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\SprykerConventions\Sniffs\Naming;

use Spryker\Test\TestCase;
use SprykerConventions\Sniffs\Naming\ForbiddenGenericClassNamesSniff;

class ForbiddenGenericClassNamesSniffTest extends TestCase
{
    public function testGivenAZedBusinessClassWithAGenericSuffixWhenSniffedThenAWarningIsReported(): void
    {
        // Arrange
        $sniff = new ForbiddenGenericClassNamesSniff();

        // Act & Assert
        $this->assertSnifferFindsWarnings($sniff, 1);
    }
}
