<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\PHP;

use Spryker\Sniffs\PHP\DeclareStrictTypesAfterFileDocSniff;
use Spryker\Test\TestCase;

class DeclareStrictTypesAfterFileDocSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDeclareStrictTypesSniffer(): void
    {
        $this->assertSnifferFindsErrors(new DeclareStrictTypesAfterFileDocSniff(), 2);
    }

    /**
     * @return void
     */
    public function testEmptyEnclosingLineFixer(): void
    {
        $this->assertSnifferCanFixErrors(new DeclareStrictTypesAfterFileDocSniff());
    }
}
