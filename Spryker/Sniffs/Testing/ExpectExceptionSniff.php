<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Testing;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Ensures no assert*() usage after expectException() calls, as those are no-op.
 *
 * @author Mark Scherer
 * @license MIT
 */
class ExpectExceptionSniff extends AbstractSprykerSniff
{
    /**
     * @var string
     */
    protected const METHOD_EXPECT_EXCEPTION = 'expectException';

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_FUNCTION];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        if (!$this->isTest($phpcsFile, $stackPtr)) {
            return;
        }

        $this->assertNoAssertsAfterExpectException($phpcsFile, $stackPtr);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function assertNoAssertsAfterExpectException(
        File $phpcsFile,
        int $stackPtr
    ): void {
        $tokens = $phpcsFile->getTokens();
        if (empty($tokens[$stackPtr]['scope_opener'])) {
            return;
        }

        $curlyBraceStartIndex = $tokens[$stackPtr]['scope_opener'];
        $curlyBraceEndIndex = $tokens[$stackPtr]['scope_closer'];

        for ($i = $curlyBraceStartIndex + 1; $i < $curlyBraceEndIndex; $i++) {
            if ($tokens[$i]['code'] !== T_VARIABLE || $tokens[$i]['content'] !== '$this') {
                continue;
            }
            if ($tokens[$i + 1]['code'] !== T_OBJECT_OPERATOR) {
                continue;
            }
            if ($tokens[$i + 2]['code'] !== T_STRING) {
                continue;
            }

            $tokenContent = $tokens[$i + 2]['content'];
            $exceptionStrings = [
                'expectException',
                'expectExceptionObject',
                'expectExceptionCode',
                //TOOD more? *Message *MessageObject ?
            ];
            if (!in_array($tokenContent, $exceptionStrings, true)) {
                continue;
            }

            $this->assertNoFollowingAsserts($phpcsFile, $i + 2, $curlyBraceEndIndex);

            break;
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $expectationIndex
     * @param int $endIndex
     *
     * @return void
     */
    protected function assertNoFollowingAsserts(File $phpcsFile, int $expectationIndex, int $endIndex): void
    {
        $tokens = $phpcsFile->getTokens();

        for ($i = $expectationIndex + 1; $i < $endIndex; $i++) {
            if ($tokens[$i]['code'] !== T_VARIABLE || $tokens[$i]['content'] !== '$this') {
                continue;
            }
            if ($tokens[$i + 1]['code'] !== T_OBJECT_OPERATOR) {
                continue;
            }
            if ($tokens[$i + 2]['code'] !== T_STRING) {
                continue;
            }

            $tokenContent = $tokens[$i + 2]['content'];
            if (!preg_match('/assert[A-Z].+/', $tokenContent)) {
                continue;
            }

            $phpcsFile->addError('expect*() call must not be followed by assert*() calls.', $i, 'InvalidAssert');

            break;
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return bool
     */
    protected function isTest(File $phpcsFile, int $stackPtr): bool
    {
        $filename = $phpcsFile->getFilename();
        if (substr($filename, -8) !== 'Test.php' && substr($filename, -9) !== 'Mocks.php') {
            return false;
        }

        return true;
    }
}
