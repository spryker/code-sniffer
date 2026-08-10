<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\SprykerConventions\Sniffs\Naming;

use Spryker\Test\TestCase;
use SprykerConventions\Sniffs\Naming\DisallowHydrateSniff;

class DisallowHydrateSniffTest extends TestCase
{
    public function testGivenSymbolsUsingTheHydrateVocabularyWhenSniffedThenErrorsAreReported(): void
    {
        // Arrange
        $sniff = new DisallowHydrateSniff();

        // Act & Assert
        $this->assertSnifferFindsErrors($sniff, 2);
    }
}
