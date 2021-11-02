<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Tools;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

class SniffsAndTests
{
    /**
     * @var array<string>
     */
    protected static $orgs = [
        'Spryker',
        'SprykerStrict',
        //'GlueStreamSpecific',
    ];

    /**
     * @param string $path Path
     *
     * @return array<string, array<string, mixed>>
     */
    public function untested(string $path): array
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $sniffs = [];

        foreach (static::$orgs as $org) {
            $directoryIterator = new RecursiveDirectoryIterator($path . $org);
            $recursiveIterator = new RecursiveIteratorIterator($directoryIterator);
            $regexIterator = new RegexIterator($recursiveIterator, '#^.+/(\w+)/Sniffs/(\w+)/(\w+)Sniff\.php$#', RecursiveRegexIterator::GET_MATCH);

            foreach ($regexIterator as $match) {
                $org = $match[1];
                $type = $match[2];
                $name = $match[3];
                $testFile = $path . 'tests' . DIRECTORY_SEPARATOR . $org . 'Sniffs' . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $name . 'SniffTest.php';
                $hasTest = file_exists($testFile);
                if ($hasTest) {
                    continue;
                }

                $key = $org . '.' . $type . '.' . $name;
                $sniffs[$key] = [
                    'hasTest' => $hasTest,
                ];
            }
        }

        return $sniffs;
    }
}
