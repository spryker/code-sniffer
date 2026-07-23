<?php

namespace PyzTest\Zed\Sales\Business;

class SalesFacadeTest
{
    public function testGivenATestMethodWithoutSectionCommentsWhenSniffedThenAWarningIsReported(): void
    {
        $result = 1 + 1;

        assert($result === 2);
    }

    public function testGivenAValueWhenItIsIncrementedThenTheResultIsCorrect(): void
    {
        // Arrange
        $value = 2;

        // Act
        $result = $value + 2;

        // Assert
        assert($result === 4);
    }

    public function testGivenAnInvalidInputWhenItIsProcessedThenAnExceptionIsThrown(): void
    {
        // Arrange
        $value = 'invalid';

        // Expect
        $expectedException = true;

        // Act
        assert($expectedException === true && $value === 'invalid');
    }

    public function helperMethod(): void
    {
    }
}
