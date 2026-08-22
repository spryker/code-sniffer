<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\Commenting;

use Spryker\Sniffs\Commenting\AttributesSniff;
use Spryker\Test\TestCase;

class AttributesSniffTest extends TestCase
{
    /**
     * @var int
     */
    protected const EXPECTED_ERROR_COUNT = 5;

    /**
     * @return void
     */
    public function testAttributesSniffer(): void
    {
        $errors = $this->assertSnifferFindsErrors(new AttributesSniff(), static::EXPECTED_ERROR_COUNT);

        $this->assertSame(
            [40, 45, 50, 57, 62],
            array_keys($errors),
            'Only the attributes that are neither fully qualified nor imported should be reported.',
        );
    }
}
