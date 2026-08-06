<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\SprykerConventions\Sniffs\Persistence;

use Spryker\Test\TestCase;
use SprykerConventions\Sniffs\Persistence\DbSpecificSqlSniff;

class DbSpecificSqlSniffTest extends TestCase
{
    public function testGivenPersistenceLayerCodeWithDatabaseSpecificSqlWhenSniffedThenErrorsAreReported(): void
    {
        // Arrange
        $sniff = new DbSpecificSqlSniff();

        // Act & Assert
        $this->assertSnifferFindsErrors($sniff, 3);
    }
}
