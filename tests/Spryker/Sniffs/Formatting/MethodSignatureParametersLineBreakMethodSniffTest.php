<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace CodeSnifferTest\Spryker\Sniffs\Formatting;

use CodeSnifferTest\TestCase;
use Spryker\Sniffs\Formatting\MethodSignatureParametersLineBreakMethodSniff;

class MethodSignatureParametersLineBreakMethodSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testMethodSignatureParametersLineBreakMethodSniffer(): void
    {
        $errors = $this->assertSnifferFindsFixableErrors(new MethodSignatureParametersLineBreakMethodSniff(), 7, 7);
        //$this->debug($errors);
    }

    /**
     * @return void
     */
    public function testMethodSignatureParametersLineBreakMethodFixer(): void
    {
        $this->assertSnifferCanFixErrors(new MethodSignatureParametersLineBreakMethodSniff());
    }
}
