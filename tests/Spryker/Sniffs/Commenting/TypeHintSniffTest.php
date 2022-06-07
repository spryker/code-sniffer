<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\Commenting;

use Spryker\Sniffs\Commenting\TypeHintSniff;
use Spryker\Test\TestCase;

class TypeHintSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testTypeHintSniffer(): void
    {
        $this->assertSnifferFindsErrors(new TypeHintSniff(), 10);
    }

    /**
     * @return void
     */
    public function testDocBlockThrowsFixer(): void
    {
        $this->assertSnifferCanFixErrors(new TypeHintSniff());
    }
}
