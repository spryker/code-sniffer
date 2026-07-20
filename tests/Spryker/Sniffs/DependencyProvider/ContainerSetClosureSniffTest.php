<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\DependencyProvider;

use Spryker\Sniffs\DependencyProvider\ContainerSetClosureSniff;
use Spryker\Test\TestCase;

class ContainerSetClosureSniffTest extends TestCase
{
    public function testContainerSetClosureSniffer(): void
    {
        $this->assertSnifferFindsErrors(new ContainerSetClosureSniff(), 2);
    }
}
