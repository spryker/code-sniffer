<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\ControlStructures;

use Spryker\Sniffs\ControlStructures\DisallowCloakingCheckSniff;
use Spryker\Test\TestCase;

class DisallowCloakingCheckSniffTest extends TestCase
{
    public function testDisallowArrayTypeHintSyntaxSniffer(): void
    {
        $this->assertSnifferFindsErrors(new DisallowCloakingCheckSniff(), 10);
    }

    public function testDocBlockThrowsFixer(): void
    {
        $this->assertSnifferCanFixErrors(new DisallowCloakingCheckSniff());
    }
}
