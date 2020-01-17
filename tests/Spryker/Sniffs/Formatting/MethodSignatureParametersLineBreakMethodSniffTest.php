<?php

namespace CodeSnifferTest\Spryker\Sniffs\Formatting;

use CodeSnifferTest\TestCase;
use Spryker\Sniffs\Formatting\MethodSignatureParametersLineBreakMethodSniff;

class MethodSignatureParametersLineBreakMethodSniffTest extends TestCase
{
    public function testMethodSignatureParametersLineBreakMethodSniffer()
    {
        $this->assertSnifferFindsFixableErrors(new MethodSignatureParametersLineBreakMethodSniff(), 7);
    }

    public function testMethodSignatureParametersLineBreakMethodFixer()
    {
        $this->assertSnifferCanFixErrors(new MethodSignatureParametersLineBreakMethodSniff(), 7);
    }
}
