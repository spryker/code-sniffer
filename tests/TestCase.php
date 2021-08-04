<?php declare(strict_types = 1);

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace CodeSnifferTest;

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
    protected const FILE_BEFORE = 'before.php';
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

        return $file->getErrors();
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

        $file = implode(DIRECTORY_SEPARATOR, [
            __DIR__,
            '_data',
            $className,
            $fileName,
        ]);
        if (!file_exists($file)) {
            $this->fail(sprintf('File not found: %s.', $file));
        }

        return $file;
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
}
