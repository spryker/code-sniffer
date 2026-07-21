<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\SprykerConventions\Sniffs\Naming;

use Spryker\Test\TestCase;
use SprykerConventions\Sniffs\Naming\PluginPrePostMethodsSniff;

class PluginPrePostMethodsSniffTest extends TestCase
{
    public function testGivenPluginMethodsPrefixedWithBeforeOrAfterWhenSniffedThenErrorsAreReported(): void
    {
        // Arrange
        $sniff = new PluginPrePostMethodsSniff();

        // Act & Assert
        $this->assertSnifferFindsErrors($sniff, 2);
    }
}
