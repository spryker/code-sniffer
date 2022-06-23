#!/usr/bin/env php
<?php

$command = 'vendor/bin/phpcs -e --standard=SprykerStrict/ruleset.xml';

exec($command, $output, $ret);
if ($ret !== 0) {
    exit('Invalid execution. Run from ROOT after composer install etc as `composer docs`.' . PHP_EOL);
}

/** @noinspection ForeachSourceInspection */
foreach ($output as &$row) {
    $row = str_replace('  ', '- ', $row);
}
unset($row);

$content = implode(PHP_EOL, $output);

$content = <<<TEXT
# Spryker Code Sniffer

$content

TEXT;

$file = __DIR__ . DIRECTORY_SEPARATOR . 'README.md';

file_put_contents($file, $content);
exit($ret);
