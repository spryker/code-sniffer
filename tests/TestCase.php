<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spryker\Test;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Runner;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use ReflectionClass;

/**
 * To run your sniffer's test, you need to place the `before.php` and `after.php` (optional) files in a folder
 * named exactly like your sniffer name without `Sniff` under _data.
 * (ie: MethodSignatureParametersLineBreakMethodSniff => MethodSignatureParametersLineBreakMethod)
 */
class TestCase extends PHPUnitTestCase
{
    protected const string FILE_BEFORE = 'before.php';

    protected const string FILE_AFTER = 'after.php';

    /**
     * This will run code sniffer
     *
     * @return array<array>
     */
    protected function assertSnifferFindsErrors(Sniff $sniffer, int $errorCount): array
    {
        return $this->runFixer($sniffer, $errorCount);
    }

    /**
     * This will run code sniffer and assert the number of warnings found.
     *
     * @param \PHP_CodeSniffer\Sniffs\Sniff $sniffer
     * @param int $warningCount
     * @param int|null $errorCount
     *
     * @return array<array>
     */
    protected function assertSnifferFindsWarnings(Sniff $sniffer, int $warningCount, ?int $errorCount = null): array
    {
        return $this->runFixer($sniffer, $errorCount, null, false, $warningCount);
    }

    /**
     * This will run code sniffer
     *
     * @return array<array>
     */
    protected function assertSnifferFindsFixableErrors(Sniff $sniffer, ?int $errorCount, int $fixableErrorCount): array
    {
        return $this->runFixer($sniffer, $errorCount, $fixableErrorCount);
    }

    /**
     * This will run code sniffer and code fixer.
     */
    protected function assertSnifferCanFixErrors(Sniff $sniffer, ?int $fixableErrorCount = null): void
    {
        $this->runFixer($sniffer, null, $fixableErrorCount, true);
    }

    /**
     * @return array<array>
     */
    protected function runFullFixer(
        string $pathBefore,
        string $pathAfter,
        ?int $errorCount = null,
        ?int $fixableErrorCount = null,
        bool $fix = false
    ): array {
        $codeSniffer = new Runner();
        $codeSniffer->config = new Config([
            '--standard=Spryker',
            '-s',
        ]);
        $codeSniffer->init();
        $codeSniffer->ruleset->populateTokenListeners();
        $file = new LocalFile($pathBefore, $codeSniffer->ruleset, $codeSniffer->config);

        if ($fix) {
            $file->fixer->enabled = true;
        }

        $file->process();

        if ($fix && $this->isDebug()) {
            if (!is_dir(TMP)) {
                mkdir(TMP, 0770, true);
            }
            file_put_contents(TMP . 'after.php', $file->fixer->getContents());
        }

        $diff = null;
        if ($fix) {
            $diff = $file->fixer->generateDiff($pathAfter);
        }

        $errors = $file->getErrors();

        if ($errorCount !== null) {
            $this->assertEquals($errorCount, $file->getErrorCount());
        }
        if ($fixableErrorCount !== null) {
            $this->assertEquals($fixableErrorCount, $file->getFixableCount());
        }

        $file->cleanUp();

        if (!$fix && $this->isDebug()) {
            $error = $this->getFormattedErrors($errors);
            echo $error;
        }
        if ($fix) {
            $this->assertSame('', $diff, $diff);
        }

        return $errors;
    }

    /**
     * @return array<array>
     */
    protected function runFixer(
        Sniff $sniffer,
        ?int $errorCount = null,
        ?int $fixableErrorCount = null,
        bool $fix = false,
        ?int $warningCount = null
    ): array {
        $codeSniffer = new Runner();
        $codeSniffer->config = new Config([
            '-s',
        ]);
        $codeSniffer->init();
        $codeSniffer->ruleset->sniffs = [get_class($sniffer) => $sniffer];
        $codeSniffer->ruleset->populateTokenListeners();
        $file = new LocalFile($this->getDummyFileBefore($sniffer), $codeSniffer->ruleset, $codeSniffer->config);

        if ($fix) {
            $file->fixer->enabled = true;
        }

        $file->process();

        if ($errorCount !== null) {
            $this->assertEquals($errorCount, $file->getErrorCount());
        }
        if ($warningCount !== null) {
            $this->assertEquals($warningCount, $file->getWarningCount());
        }
        if ($fixableErrorCount !== null) {
            $this->assertEquals($fixableErrorCount, $file->getFixableCount());
        }

        if ($fix) {
            $diff = $file->fixer->generateDiff($this->getDummyFileAfter($sniffer));
            $this->assertSame('', $diff, $diff);
        }

        $file->cleanUp();

        $errors = $file->getErrors();

        if (!$fix && $this->isDebug()) {
            $error = $this->getFormattedErrors($errors);
            echo $error;
        }

        return $errors;
    }

    protected function getDummyFileBefore(Sniff $sniffer): string
    {
        return $this->getDummyFile($sniffer, static::FILE_BEFORE);
    }

    protected function getDummyFileAfter(Sniff $sniffer): string
    {
        return $this->getDummyFile($sniffer, static::FILE_AFTER);
    }

    protected function getDummyFile(Sniff $sniffer, string $fileName): string
    {
        $className = (new ReflectionClass($sniffer))->getShortName();
        $className = str_replace('Sniff', '', $className);

        $file = $this->testFilePath() . $className . DS . $fileName;
        if (!file_exists($file)) {
            $this->fail(sprintf('File not found: %s.', $file));
        }

        return $file;
    }

    protected function testFilePath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            __DIR__,
            '_data',
        ]) . DIRECTORY_SEPARATOR;
    }

    /**
     * Checks if debug flag is set.
     *
     * Flag is set via `--debug`.
     * Allows additional stuff like non-mocking when enabling debug.
     *
     * @return bool Success
     */
    protected function isDebug(): bool
    {
        return !empty($_SERVER['argv']) && in_array('--debug', $_SERVER['argv'], true);
    }

    /**
     * Checks if verbose flag is set.
     *
     * Flags are `-v` and `-vv`.
     * Allows additional stuff like non-mocking when enabling debug.
     *
     * @param bool $onlyVeryVerbose If only -vv should be counted.
     *
     * @return bool Success
     */
    protected function isVerbose(bool $onlyVeryVerbose = false): bool
    {
        if (empty($_SERVER['argv'])) {
            return false;
        }
        if (!$onlyVeryVerbose && in_array('-v', $_SERVER['argv'], true)) {
            return true;
        }
        if (in_array('-vv', $_SERVER['argv'], true)) {
            return true;
        }

        return false;
    }

    /**
     * @param array<array<array<string|int|bool>>> $errors
     */
    protected function getFormattedErrors(array $errors): string
    {
        $lines = [];
        foreach ($errors as $line => $lineErrors) {
            $line = str_pad((string)$line, 4, ' ', STR_PAD_LEFT);

            $lines[] = $line . ' | ' . implode(PHP_EOL, array_map(static function (array $errors): string {
                return implode(PHP_EOL, array_map(static function (array $error): string {
                    $fixable = $error['fixable'] ? '[x]' : '[ ]';

                    return sprintf('%s %s: %s', $fixable, $error['source'], $error['message']);
                }, $errors));
            }, $lineErrors));
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }
}
