<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Spryker\Sniffs;

use Spryker\Test\TestCase;

/**
 * Test integration of all sniffs together.
 */
class AllSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testAllSniffs(): void
    {
        $before = $this->testFilePath() . 'All' . DS . 'before.php';
        $after = $this->testFilePath() . 'All' . DS . 'after.php';

        // Use --debug to display the errors found
        $errors = $this->runFullFixer($before, $after);
        $this->assertNotEmpty($errors);

        $this->runFullFixer($before, $after, null, null, true);
    }
}
