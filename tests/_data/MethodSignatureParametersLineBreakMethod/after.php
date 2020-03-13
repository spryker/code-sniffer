<?php declare(strict_types = 1);

namespace Spryker;

class FixMe
{
    public function thisIsAVeryLongFunctionNameAndShouldBeMultiLinedIReckonAndPleaseAutoFixedSoIDontHaveToDoItManually(
        string $foo,
        ?int $barbarbarbarbar = null
    ): ?array {
    }

    public function shouldBeSingleLine(int $test): int
    {
    }

    public function shouldBeSingleLine2(int $test)
    {
    }

    public function shouldBeSingleLine3(int $test)
    {
    }

    public function shouldBeSingleLine4(int $foo, string $bar)
    {
    }

    abstract function shouldBeSingleLine5($foo);

    abstract function thisIsAVeryLongFunctionNameAndShouldBeMultiLinedIReckonAndPleaseAutoFixedSoIDontHaveToDoItManually2(
        string $foo,
        ?int $barbarbarbarbar = null
    );

    //the following are correct and should not show errors.

    public function thisIsAVeryLongFunctionName(array $test, string $foo, ?int $barbarbarbarbar = null): ?array
    {
    }

    public function thisIsAVeryLongFunctionName2(
        array $test,
        string $foo,
        ?int $barbarbarbarbar = null
    ): ?array {
    }

    public function thisIsAVeryLongFunctionName3(
        array $foo,
        string $bar,
        ?int $i = null,
        ?int $j = 0
    ): ?array {
    }

    public function isCorrect(int $test)
    {
    }

    public function thisIsATrulyVeryLengthyFunctionNameAndShouldNotBeMultiLinedIReckonButItDefinitelyShouldBeIgnored(): ?array
    {
    }
}
