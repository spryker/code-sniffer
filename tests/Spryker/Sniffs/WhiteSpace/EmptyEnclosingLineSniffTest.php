<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\WhiteSpace;

use Spryker\Sniffs\WhiteSpace\EmptyEnclosingLineSniff;
use Spryker\Test\TestCase;

class EmptyEnclosingLineSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testEmptyEnclosingLineSniffer(): void
    {
        $this->assertSnifferFindsErrors(new EmptyEnclosingLineSniff(), 2);
    }

    /**
     * @return void
     */
    public function testEmptyEnclosingLineFixer(): void
    {
        $this->assertSnifferCanFixErrors(new EmptyEnclosingLineSniff());
    }
}
