<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace CodeSnifferTest\Spryker\Sniffs\ControlStructures;

use CodeSnifferTest\TestCase;
use Spryker\Sniffs\ControlStructures\DisallowCloakingCheckSniff;

class DisallowCloakingCheckSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDisallowArrayTypeHintSyntaxSniffer(): void
    {
        $this->assertSnifferFindsErrors(new DisallowCloakingCheckSniff(), 12);
    }

    /**
     * @return void
     */
    public function testDocBlockThrowsFixer(): void
    {
        $this->assertSnifferCanFixErrors(new DisallowCloakingCheckSniff());
    }
}
