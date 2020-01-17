<?php declare(strict_types = 1);

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
 *
 * @package CodeSnifferTest
 */
class TestCase extends PHPUnitTestCase
{
    protected const FILE_BEFORE = 'before.php';
    protected const FILE_AFTER = 'after.php';

    /**
     * This will run code sniffer
     *
     * @param \PHP_CodeSniffer\Sniffs\Sniff $sniffer
     * @param int $fixableErrorCount
     *
     * @throws \PHP_CodeSniffer\Exceptions\DeepExitException
     */
    protected function assertSnifferFindsFixableErrors(Sniff $sniffer, int $fixableErrorCount): void
    {
        $this->runFixer($sniffer, $fixableErrorCount);
    }

    /**
     * This will run code sniffer and code fixer.
     *
     * @param \PHP_CodeSniffer\Sniffs\Sniff $sniffer
     * @param int $fixableErrorCount
     *
     * @throws \PHP_CodeSniffer\Exceptions\DeepExitException
     */
    protected function assertSnifferCanFixErrors(Sniff $sniffer, int $fixableErrorCount): void
    {
        $this->runFixer($sniffer, $fixableErrorCount, true);
    }

    /**
     * @param \PHP_CodeSniffer\Sniffs\Sniff $sniffer
     * @param int|null $fixableErrorCount
     * @param bool $fix
     *
     * @throws \PHP_CodeSniffer\Exceptions\DeepExitException
     */
    protected function runFixer(
        Sniff $sniffer,
        ?int $fixableErrorCount = null,
        bool $fix = false
    ): void
    {
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
        if ($fixableErrorCount !== null) {
            $this->assertEquals($fixableErrorCount, $file->getFixableCount());
        }

        if ($fix) {
            $diff = $file->fixer->generateDiff($this->getDummyFileAfter($sniffer));
            $this->assertSame('', $diff, $diff);
        }

        $file->cleanUp();
    }

    /**
     * @param \PHP_CodeSniffer\Sniffs\Sniff $sniffer
     *
     * @return string
     */
    protected function getDummyFileBefore(Sniff $sniffer) {
        return $this->getDummyFile($sniffer, static::FILE_BEFORE);
    }

    /**
     * @param \PHP_CodeSniffer\Sniffs\Sniff $sniffer
     *
     * @return string
     */
    protected function getDummyFileAfter(Sniff $sniffer) {
        return $this->getDummyFile($sniffer, static::FILE_AFTER);
    }

    /**
     * @param \PHP_CodeSniffer\Sniffs\Sniff $sniffer
     * @param string $fileName
     *
     * @return string
     * @throws \ReflectionException
     */
    protected function getDummyFile(Sniff $sniffer, string $fileName): string
    {
        $className = (new ReflectionClass($sniffer))->getShortName();
        $className = str_replace('Sniff', '', $className);

        $file = implode(DIRECTORY_SEPARATOR, [
            __DIR__,
            '_data',
            $className,
            $fileName
        ]);
        if (!file_exists($file)) {
            $this->fail(sprintf('File not found: %s.', $file));
        }
        return $file;
    }
}
