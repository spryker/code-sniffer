<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\Commenting;

use Spryker\Sniffs\Commenting\FullyQualifiedClassNameInDocBlockSniff;
use Spryker\Test\TestCase;

class FullyQualifiedClassNameInDocBlockSniffTest extends TestCase
{
    public function testDocBlockConstSniffer(): void
    {
        $this->assertSnifferFindsErrors(new FullyQualifiedClassNameInDocBlockSniff(), 11);
    }

    public function testDocBlockConstFixer(): void
    {
        $this->assertSnifferCanFixErrors(new FullyQualifiedClassNameInDocBlockSniff());
    }
}
