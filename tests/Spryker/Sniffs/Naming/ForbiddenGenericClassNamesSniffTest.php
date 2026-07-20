<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\Naming;

use Spryker\Sniffs\Naming\ForbiddenGenericClassNamesSniff;
use Spryker\Test\TestCase;

class ForbiddenGenericClassNamesSniffTest extends TestCase
{
    public function testForbiddenGenericClassNamesSniffer(): void
    {
        $this->assertSnifferFindsWarnings(new ForbiddenGenericClassNamesSniff(), 1);
    }
}
