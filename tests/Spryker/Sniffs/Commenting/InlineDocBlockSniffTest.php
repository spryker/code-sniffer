<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\Commenting;

use Spryker\Sniffs\Commenting\InlineDocBlockSniff;
use Spryker\Test\TestCase;

class InlineDocBlockSniffTest extends TestCase
{
    public function testDocBlockConstSniffer(): void
    {
        $this->assertSnifferFindsErrors(new InlineDocBlockSniff(), 1);
    }

    public function testDocBlockConstFixer(): void
    {
        $this->assertSnifferCanFixErrors(new InlineDocBlockSniff());
    }
}
