<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\SprykerConventions\Sniffs\Commenting;

use Spryker\Test\TestCase;
use SprykerConventions\Sniffs\Commenting\ForbiddenInlineCommentsSniff;

class ForbiddenInlineCommentsSniffTest extends TestCase
{
    public function testGivenCommentsWithIssueKeysBannersAndClosingBraceMarkersWhenSniffedThenEachIsReportedAndConventionsAreNot(): void
    {
        // Arrange
        $sniff = new ForbiddenInlineCommentsSniff();

        // Act & Assert
        $this->assertSnifferFindsErrors($sniff, 11);
    }
}
