<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\WhiteSpace;

use Spryker\Sniffs\WhiteSpace\EmptyLinesSniff;
use Spryker\Test\TestCase;

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
