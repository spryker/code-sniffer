<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\Naming;

use Spryker\Sniffs\Naming\PluginPrePostMethodsSniff;
use Spryker\Test\TestCase;

class PluginPrePostMethodsSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testPluginPrePostMethodsSniffer(): void
    {
        $this->assertSnifferFindsErrors(new PluginPrePostMethodsSniff(), 2);
    }
}
