<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\Commenting;

use Spryker\Sniffs\Commenting\DocBlockConstSniff;
use Spryker\Test\TestCase;

class DocBlockConstSniffTest extends TestCase
{
    public function testDocBlockConstSniffer(): void
    {
        $this->assertSnifferFindsErrors(new DocBlockConstSniff(), 4);
    }

    public function testDocBlockConstFixer(): void
    {
        $this->assertSnifferCanFixErrors(new DocBlockConstSniff());
    }
}
