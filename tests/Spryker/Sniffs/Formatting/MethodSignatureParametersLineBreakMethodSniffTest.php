<?php

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
        $this->assertSnifferFindsFixableErrors(new MethodSignatureParametersLineBreakMethodSniff(), 7);
    }

    /**
     * @return void
     */
    public function testMethodSignatureParametersLineBreakMethodFixer(): void
    {
        $this->assertSnifferCanFixErrors(new MethodSignatureParametersLineBreakMethodSniff(), 7);
    }
}
