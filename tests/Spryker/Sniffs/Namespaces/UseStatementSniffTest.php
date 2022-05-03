<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace CodeSnifferTest\Spryker\Sniffs\Namespaces;

use CodeSnifferTest\TestCase;
use Spryker\Sniffs\Namespaces\UseStatementSniff;

class UseStatementSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDocBlockConstSniffer(): void
    {
        $this->assertSnifferFindsErrors(new UseStatementSniff(), 1);
    }

    /**
     * @return void
     */
    public function testDocBlockConstFixer(): void
    {
        $this->assertSnifferCanFixErrors(new UseStatementSniff());
    }
}
