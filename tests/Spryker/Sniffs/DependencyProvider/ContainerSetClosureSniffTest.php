<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\DependencyProvider;

use Spryker\Sniffs\DependencyProvider\ContainerSetClosureSniff;
use Spryker\Test\TestCase;

class ContainerSetClosureSniffTest extends TestCase
{
    public function testGivenADependencyProviderSetsServicesWithoutClosuresWhenSniffedThenErrorsAreReported(): void
    {
        // Arrange
        $sniff = new ContainerSetClosureSniff();

        // Act & Assert
        $this->assertSnifferFindsErrors($sniff, 2);
    }
}
