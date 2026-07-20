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
    public function testTableConstantsSniffer(): void
    {
        $this->assertSnifferFindsWarnings(new TableConstantsSniff(), 1, 4);
    }
}
