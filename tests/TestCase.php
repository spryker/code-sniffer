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
    /**
     * @var string
     */
    protected const FILE_BEFORE = 'before.php';

    /**
     * @var string
     */
    protected const FILE_AFTER = 'after.php';

    /**
     * This will run code sniffer
     *
     * @param \PHP_CodeSniffer\Sniffs\Sniff $sniffer
     * @param int $errorCount
     *
     * @return array<array>
     */
    protected function assertSnifferFindsErrors(Sniff $sniffer, int $errorCount): array
    {
        return $this->runFixer($sniffer, $errorCount);
    }

    /**
     * This will run code sniffer
     *
     * @param \PHP_CodeSniffer\Sniffs\Sniff $sniffer
     * @param int|null $errorCount
     * @param int $fixableErrorCount
     *
     * @return array<array>
     */
    protected function assertSnifferFindsFixableErrors(Sniff $sniffer, ?int $errorCount, int $fixableErrorCount): array
    {
        return $this->runFixer($sniffer, $errorCount, $fixableErrorCount);
    }

    /**
     * This will run code sniffer and code fixer.
     *
     * @param \PHP_CodeSniffer\Sniffs\Sniff $sniffer
     * @param int|null $fixableErrorCount
     *
     * @return void
     */
    protected function assertSnifferCanFixErrors(Sniff $sniffer, ?int $fixableErrorCount = null): void
    {
        $this->runFixer($sniffer, null, $fixableErrorCount, true);
    }

    /**
     * @param string $pathBefore
     * @param string $pathAfter
     * @param int|null $errorCount
     * @param int|null $fixableErrorCount
     * @param bool $fix
     *
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
     * @param \PHP_CodeSniffer\Sniffs\Sniff $sniffer
     * @param int|null $errorCount
     * @param int|null $fixableErrorCount
     * @param bool $fix
     *
     * @return array<array>
     */
    protected function runFixer(
        Sniff $sniffer,
        ?int $errorCount = null,
        ?int $fixableErrorCount = null,
        bool $fix = false
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

    /**
     * @param \PHP_CodeSniffer\Sniffs\Sniff $sniffer
     *
     * @return string
     */
    protected function getDummyFileBefore(Sniff $sniffer): string
    {
        return $this->getDummyFile($sniffer, static::FILE_BEFORE);
    }

    /**
     * @param \PHP_CodeSniffer\Sniffs\Sniff $sniffer
     *
     * @return string
     */
    protected function getDummyFileAfter(Sniff $sniffer): string
    {
        return $this->getDummyFile($sniffer, static::FILE_AFTER);
    }

    /**
     * @param \PHP_CodeSniffer\Sniffs\Sniff $sniffer
     * @param string $fileName
     *
     * @return string
     */
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

    /**
     * @return string
     */
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
     *
     * @return string
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
