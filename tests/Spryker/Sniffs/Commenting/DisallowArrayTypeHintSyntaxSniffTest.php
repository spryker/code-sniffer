<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\Commenting;

use Spryker\Sniffs\Commenting\DisallowArrayTypeHintSyntaxSniff;
use Spryker\Test\TestCase;

class DisallowArrayTypeHintSyntaxSniffTest extends TestCase
{
    public function testDisallowArrayTypeHintSyntaxSniffer(): void
    {
        $this->assertSnifferFindsErrors(new DisallowArrayTypeHintSyntaxSniff(), 12);
    }

    public function testDocBlockThrowsFixer(): void
    {
        $this->assertSnifferCanFixErrors(new DisallowArrayTypeHintSyntaxSniff());
    }
}
