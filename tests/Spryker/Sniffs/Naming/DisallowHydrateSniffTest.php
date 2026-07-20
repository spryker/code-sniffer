<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\Naming;

use Spryker\Sniffs\Naming\DisallowHydrateSniff;
use Spryker\Test\TestCase;

class DisallowHydrateSniffTest extends TestCase
{
    public function testDisallowHydrateSniffer(): void
    {
        $this->assertSnifferFindsErrors(new DisallowHydrateSniff(), 2);
    }
}
