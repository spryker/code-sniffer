<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\Persistence;

use Spryker\Sniffs\Persistence\DbSpecificSqlSniff;
use Spryker\Test\TestCase;

class DbSpecificSqlSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDbSpecificSqlSniffer(): void
    {
        $this->assertSnifferFindsErrors(new DbSpecificSqlSniff(), 3);
    }
}
