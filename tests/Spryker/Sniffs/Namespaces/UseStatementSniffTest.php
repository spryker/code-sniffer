<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\Namespaces;

use Spryker\Sniffs\Namespaces\UseStatementSniff;
use Spryker\Test\TestCase;

class UseStatementSniffTest extends TestCase
{
    public function testDocBlockConstSniffer(): void
    {
        $this->assertSnifferFindsErrors(new UseStatementSniff(), 1);
    }

    public function testDocBlockConstFixer(): void
    {
        $this->assertSnifferCanFixErrors(new UseStatementSniff());
    }
}
