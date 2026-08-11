<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\Internal;

use Spryker\Sniffs\Internal\SprykerPreferStaticOverSelfSniff;
use Spryker\Test\TestCase;

class SprykerPreferStaticOverSelfSniffTest extends TestCase
{
    public function testDocBlockConstSniffer(): void
    {
        $this->assertSnifferFindsErrors(new SprykerPreferStaticOverSelfSniff(), 2);
    }

    public function testDocBlockConstFixer(): void
    {
        $this->assertSnifferCanFixErrors(new SprykerPreferStaticOverSelfSniff());
    }
}
