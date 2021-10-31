<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace CodeSnifferTest\Spryker\Sniffs\WhiteSpace;

use CodeSnifferTest\TestCase;
use Spryker\Sniffs\WhiteSpace\EmptyLinesSniff;

class EmptyLinesSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testEmptyLinesSniffer(): void
    {
        $this->assertSnifferFindsErrors(new EmptyLinesSniff(), 6);
    }

    /**
     * @return void
     */
    public function testEmptyEnclosingLineFixer(): void
    {
        $this->assertSnifferCanFixErrors(new EmptyLinesSniff());
    }
}
