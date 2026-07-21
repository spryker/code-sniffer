<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\Table;

use Spryker\Sniffs\Table\TableConstantsSniff;
use Spryker\Test\TestCase;

class TableConstantsSniffTest extends TestCase
{
    public function testGivenTableConstantsWithWrongVisibilityTypeOrMissingUsesTagWhenSniffedThenViolationsAreReported(): void
    {
        // Arrange
        $sniff = new TableConstantsSniff();

        // Act & Assert
        $this->assertSnifferFindsWarnings($sniff, 1, 4);
    }
}
