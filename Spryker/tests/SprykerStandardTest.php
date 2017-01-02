<?php

class SprykerStandardTest extends PHPUnit_Framework_TestCase
{

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        if (empty($this->helper)) {
            $this->helper = new TestHelper();
        }
    }

    /**
     * Run simple syntax checks, if the filename ends with pass.php - expect it to pass
     *
     * @return array
     */
    public static function testProvider()
    {
        $tests = [];

        $standard = dirname(__DIR__);

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/files'));
        foreach ($iterator as $dir) {
            if ($dir->isDir()) {
                continue;
            }

            $file = $dir->getPathname();
            $expectPass = (substr($file, -8) === 'Pass.php');
            $tests[] = [
                $file,
                $standard,
                $expectPass,
            ];
        }

        return $tests;
    }

    /**
     * @dataProvider testProvider
     *
     * @param string $file
     * @param string $standard
     * @param bool $expectPass
     *
     * @return void
     */
    public function testFile($file, $standard, $expectPass)
    {
        $outputStr = $this->helper->runPhpCs($file);
        if ($expectPass) {
            $this->assertNotRegExp(
                "/FOUND \\d+ ERROR/",
                $outputStr,
                basename($file) . ' - expected to pass with no errors, some were reported. '
            );
        } else {
            $this->assertRegExp(
                "/FOUND \\d+ ERROR/",
                $outputStr,
                basename($file) . ' - expected failures, none reported. '
            );
        }
    }

}
