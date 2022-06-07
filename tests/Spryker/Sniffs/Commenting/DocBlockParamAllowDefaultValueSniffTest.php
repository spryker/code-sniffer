<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\Commenting;

use Spryker\Sniffs\Commenting\DocBlockParamAllowDefaultValueSniff;
use Spryker\Test\TestCase;

class DocBlockParamAllowDefaultValueSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDocBlockConstSniffer(): void
    {
        $this->assertSnifferFindsErrors(new DocBlockParamAllowDefaultValueSniff(), 4);
    }

    /**
     * @return void
     */
    public function testDocBlockConstFixer(): void
    {
        $this->assertSnifferCanFixErrors(new DocBlockParamAllowDefaultValueSniff());
    }
}
