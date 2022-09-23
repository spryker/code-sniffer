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
    /**
     * @return void
     */
    public function testDocBlockConstSniffer(): void
    {
        $this->assertSnifferFindsErrors(new FullyQualifiedClassNameInDocBlockSniff(), 11);
    }

    /**
     * @return void
     */
    public function testDocBlockConstFixer(): void
    {
        $this->assertSnifferCanFixErrors(new FullyQualifiedClassNameInDocBlockSniff());
    }
}
