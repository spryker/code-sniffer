<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\SprykerConventions\Sniffs\Testing;

use Spryker\Test\TestCase;
use SprykerConventions\Sniffs\Testing\ArrangeActAssertCommentsSniff;

class ArrangeActAssertCommentsSniffTest extends TestCase
{
    public function testGivenATestMethodWithoutSectionCommentsWhenSniffedThenAWarningIsReported(): void
    {
        // Arrange
        $sniff = new ArrangeActAssertCommentsSniff();

        // Act & Assert
        $this->assertSnifferFindsWarnings($sniff, 1);
    }
}
