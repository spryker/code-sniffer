<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\Commenting;

use Spryker\Sniffs\Commenting\DocBlockReturnVoidSniff;
use Spryker\Test\TestCase;

class DocBlockReturnVoidSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDocBlockReturnVoidSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new DocBlockReturnVoidSniff(), 3, 3);
    }

    /**
     * @return void
     */
    public function testDocBlockReturnVoidFixer(): void
    {
        $this->assertSnifferCanFixErrors(new DocBlockReturnVoidSniff(), 3);
    }
}
