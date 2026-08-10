<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs\Commenting;

use Spryker\Sniffs\Commenting\DocBlockParamSniff;
use Spryker\Test\TestCase;

class DocBlockParamSniffTest extends TestCase
{
    /**
     * Two `ExtraParam` (stale + no-arg stray) and three `RequiredParamMissing` (array, untyped, and
     * an `array|string` union whose array member still hides the element type/shape). The
     * documented-subset, documented-union, fully-omitted-redundant and inline-`{@inheritDoc}` cases
     * must produce no error.
     *
     * @return void
     */
    public function testDocBlockParamSniffer(): void
    {
        $this->assertSnifferFindsErrors(new DocBlockParamSniff(), 5);
    }
}
