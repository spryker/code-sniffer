#!/usr/bin/env php
<?php

exec('vendor/bin/phpcs --standard=Spryker/ruleset.xml -e', $output, $ret);
if ($ret !== 0) {
    die('Invalid execution. Run from ROOT after composer install etc as `php docs/generate.php`.');
}

foreach ($output as &$row) {
    $row = str_replace('  ', '- ', $row);
}

$content = implode(PHP_EOL, $output);

$content = <<<TEXT
# Spryker Code Sniffer

$content;
TEXT;

$file = __DIR__ . DIRECTORY_SEPARATOR . 'README.md';

file_put_contents($file, $content);
exit($ret);
