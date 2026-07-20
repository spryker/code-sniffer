<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\Messenger;

use Spryker\Sniffs\Messenger\NoSprintfInMessageSniff;
use Spryker\Test\TestCase;

class NoSprintfInMessageSniffTest extends TestCase
{
    public function testNoSprintfInMessageSniffer(): void
    {
        $this->assertSnifferFindsErrors(new NoSprintfInMessageSniff(), 2);
    }
}
