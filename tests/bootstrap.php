<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$manualAutoload = dirname(__DIR__) . '/vendor/squizlabs/php_codesniffer/autoload.php';
if (!class_exists(\PHP_CodeSniffer\Config::class) && file_exists($manualAutoload)) {
    require $manualAutoload;
}
