<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\SprykerConventions\Sniffs\Messenger;

use Spryker\Test\TestCase;
use SprykerConventions\Sniffs\Messenger\NoSprintfInMessageSniff;

class NoSprintfInMessageSniffTest extends TestCase
{
    public function testGivenZedCommunicationMessagesBuiltWithSprintfWhenSniffedThenErrorsAreReported(): void
    {
        // Arrange
        $sniff = new NoSprintfInMessageSniff();

        // Act & Assert
        $this->assertSnifferFindsErrors($sniff, 2);
    }
}
