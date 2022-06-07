#!/usr/bin/env php
<?php
require dirname(__DIR__) . '/vendor/autoload.php';

/*
 * Generate test case and before/after files for a sniff
 */
if (empty($_SERVER['argv'][1])) {
    echo 'No sniff passed. Make sure to run as `php tests/generate.php MyNamespace.MyType.MySniffName` or use `"FQCN"`. It must be a sniff from this package.'
        . PHP_EOL . PHP_EOL;

    $sniffs = (new \Spryker\Tools\SniffsAndTests())->untested(dirname(__DIR__));

    if ($sniffs) {
        echo 'The following sniffs are untested:' . PHP_EOL;
    } else {
        echo 'All sniffs are currently tested :-)' . PHP_EOL;
    }
    foreach ($sniffs as $sniff => $info) {
        echo '- ' . $sniff . PHP_EOL;
    }

    exit(count($sniffs) === 0 ? 0 : 1);
}

$sniff = trim($_SERVER['argv'][1], '\\');

if (!preg_match('/^[\w\\\\]+Sniff$/', $sniff) && !preg_match('/\w+\.\w+\.\w+/', $sniff)) {
    exit('Invalid sniff passed. Make sure to run as `php tests/generate.php MyNamespace.MyType.MySniffName` or use `"FQCN"`. It must be a sniff from this package.' . PHP_EOL);
}

$org = $type = $name = null;
if (strpos($sniff, '.') !== false) {
    $pieces = explode('.', $sniff, 3);
    if (count($pieces) !== 3) {
        exit('Invalid sniff passed. `MyNamespace.MyType.MySniffName` is the valid 3 part string for dot syntax.' . PHP_EOL);
    }
    [$org, $type, $name] = $pieces;
} else {
    preg_match('/^(\w+)\\\\Sniffs\\\\(\w+)\\\\(\w+)Sniff$/', $sniff, $matches);
    if (!$matches) {
        exit('Invalid sniff passed. `Full\ClassName\To\MySniff` is the valid string for FQCN syntax.' . PHP_EOL);
    }
    [, $org, $type, $name] = $matches;
}

$sniffPath = dirname(__DIR__) . DIRECTORY_SEPARATOR
    . $org . DIRECTORY_SEPARATOR
    . 'Sniffs' . DIRECTORY_SEPARATOR
    . $type . DIRECTORY_SEPARATOR
    . $name . 'Sniff.php';
if (!file_exists($sniffPath)) {
    exit('No such sniff found: `' . $sniffPath . '`.' . PHP_EOL);
}

$testClassContent = <<<TEXT
<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\\Test\\{$org}\\Sniffs\\{$type};

use Spryker\\Test\\TestCase;
use {$org}\\Sniffs\\{$type}\\{$name}Sniff;

class {$name}SniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDocBlockConstSniffer(): void
    {
        \$this->assertSnifferFindsErrors(new {$name}Sniff(), 1);
    }

    /**
     * @return void
     */
    public function testDocBlockConstFixer(): void
    {
        \$this->assertSnifferCanFixErrors(new {$name}Sniff());
    }
}

TEXT;

$fixtureContent = <<<TEXT
<?php declare(strict_types = 1);

namespace {$org};

class FixMe
{
}

TEXT;

$testDir = __DIR__ . DIRECTORY_SEPARATOR . $org . DIRECTORY_SEPARATOR . 'Sniffs' . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR;
$fixtureDir = __DIR__ . DIRECTORY_SEPARATOR . '_data' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR;

$testClassFile = $testDir . $name . 'SniffTest.php';
$fixtureBeforeFile = $fixtureDir . 'before.php';
$fixtureAfterFile = $fixtureDir . 'after.php';

if (!is_dir($testDir)) {
    mkdir($testDir, 0770, true);
}
if (!is_dir($fixtureDir)) {
    mkdir($fixtureDir, 0770, true);
}

if (!file_exists($testClassFile) || (!empty($_SERVER['argv'][2]) && $_SERVER['argv'][2] === '-f')) {
    file_put_contents($testClassFile, $testClassContent);
}
if (!file_exists($fixtureBeforeFile) || (!empty($_SERVER['argv'][2]) && $_SERVER['argv'][2] === '-f')) {
    file_put_contents($fixtureBeforeFile, $fixtureContent);
}
if (!file_exists($fixtureAfterFile) || (!empty($_SERVER['argv'][2]) && $_SERVER['argv'][2] === '-f')) {
    file_put_contents($fixtureAfterFile, $fixtureContent);
}

exit(0);
