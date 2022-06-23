<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

$phpcsAutoload = dirname(__DIR__) . '/vendor/squizlabs/php_codesniffer/autoload.php';
if (!class_exists(PHP_CodeSniffer\Config::class) && is_file($phpcsAutoload)) {
    require_once $phpcsAutoload;
    unset($phpcsAutoload);
}

PHP_CodeSniffer\Autoload::load(PHP_CodeSniffer\Util\Tokens::class);

if (!defined('PHP_CODESNIFFER_CBF')) {
    define('PHP_CODESNIFFER_CBF', false);
}

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('TMP')) {
    define('TMP', __DIR__ . DS . 'tmp' . DS);
}
