<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace CodeSnifferTest\Spryker\Sniffs\WhiteSpace;

use CodeSnifferTest\TestCase;
use Spryker\Sniffs\WhiteSpace\OperatorSpacingSniff;

class OperatorSpacingSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testOperatorSpacingSniffer(): void
    {
        $this->assertSnifferFindsErrors(new OperatorSpacingSniff(), 0); //FIXME
    }

    /**
     * @return void
     */
    public function testEmptyEnclosingLineFixer(): void
    {
        $this->assertSnifferCanFixErrors(new OperatorSpacingSniff());
    }
}
