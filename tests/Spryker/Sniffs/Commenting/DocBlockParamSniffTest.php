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
     * Two `ExtraParam` (stale + no-arg stray) and two `RequiredParamMissing` (array + untyped).
     * The documented-subset and fully-omitted-redundant cases must produce no error.
     *
     * @return void
     */
    public function testDocBlockParamSniffer(): void
    {
        $this->assertSnifferFindsErrors(new DocBlockParamSniff(), 4);
    }
}
