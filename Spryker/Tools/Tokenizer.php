<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Tools;

use Exception;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Ruleset;
use PHP_CodeSniffer\Runner;
use RuntimeException;

$manualAutoload = getcwd() . '/vendor/squizlabs/php_codesniffer/autoload.php';
if (!class_exists(Config::class) && file_exists($manualAutoload)) {
    require $manualAutoload;
}

class Tokenizer
{
    /**
     * @var string
     */
    protected $root;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var bool
     */
    protected $verbose;

    /**
     * @param array<string> $argv
     *
     * @throws \Exception
     */
    public function __construct(array $argv)
    {
        $file = !empty($argv[1]) ? $argv[1] : null;
        if (!$file || !file_exists($file)) {
            throw new Exception('Please provide a valid file.');
        }
        $file = realpath($file);
        if ($file === false) {
            throw new Exception('Please provide a valid file.');
        }

        $this->root = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR;
        $this->path = $file;
        $this->verbose = !empty($argv[2]) && in_array($argv[2], ['--verbose', '-v']);
    }

    /**
     * @return void
     */
    public function tokenize(): void
    {
        $res = [];
        $tokens = $this->getTokens($this->path);

        $array = file($this->path) ?: [];
        foreach ($array as $key => $row) {
            $res[] = rtrim($row);
            $tokenStrings = $this->getTokenStrings($key + 1, $tokens);
            if ($tokenStrings) {
                foreach ($tokenStrings as $string) {
                    $res[] = '// ' . $string;
                }
            }
        }
        $content = implode(PHP_EOL, $res);
        echo 'Tokenizing: ' . $this->path . PHP_EOL;
        $newPath = dirname($this->path) . DIRECTORY_SEPARATOR . pathinfo($this->path, PATHINFO_FILENAME) . '.tokens.' . pathinfo($this->path, PATHINFO_EXTENSION);
        file_put_contents($newPath, $content);
        echo 'Token file: ' . $newPath . PHP_EOL;
    }

    /**
     * @param string $path Path
     *
     * @throws \RuntimeException
     *
     * @return array<int, array<string, mixed>> Tokens
     */
    protected function getTokens(string $path): array
    {
        $phpcs = new Runner();

        define('PHP_CODESNIFFER_CBF', false);

        $config = new Config(['--standard=PSR2']);
        $phpcs->config = $config;
        $phpcs->init();

        $ruleset = new Ruleset($config);

        $file = new File($path, $ruleset, $config);
        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('Content not found for path ' . $path);
        }
        $file->setContent($content);
        $file->parse();

        return $file->getTokens();
    }

    /**
     * @param int $row Current row
     * @param array<int, array<string, mixed>> $tokens Tokens array
     *
     * @return array<string>
     */
    protected function getTokenStrings(int $row, array $tokens): array
    {
        $pieces = [];
        foreach ($tokens as $key => $token) {
            if ($token['line'] > $row) {
                break;
            }
            if ($token['line'] < $row) {
                continue;
            }
            if ($this->verbose) {
                $type = $token['type'];
                $content = $token['content'];
                $content = '`' . str_replace(["\r\n", "\n", "\r", "\t"], ['\r\n', '\n', '\r', '\t'], $content) . '`';

                unset($token['type']);
                unset($token['content']);
                $token['content'] = $content;

                $tokenList = [];
                foreach ($token as $k => $v) {
                    if (is_array($v)) {
                        if (!$v) {
                            continue;
                        }
                        $v = json_encode($v);
                    }
                    $tokenList[] = $k . '=' . $v;
                }
                $pieces[] = $type . ' (' . $key . ') ' . implode(', ', $tokenList);
            } else {
                $pieces[] = $token['type'];
            }
        }
        if ($this->verbose) {
            return $pieces;
        }

        return [implode(' ', $pieces)];
    }
}
