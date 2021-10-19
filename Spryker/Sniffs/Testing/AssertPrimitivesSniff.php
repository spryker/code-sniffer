<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Testing;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Ensures no assertSame() usage for primitives that have their own method.
 *
 * @author Mark Scherer
 * @license MIT
 */
class AssertPrimitivesSniff extends AbstractSprykerSniff
{
    /**
     * @var string
     */
    protected const METHOD_ASSERT_SAME = 'assertSame';

    /**
     * @var array<string>
     */
    protected static $primitives = [
        'null',
        'true',
        'false',
    ];

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

        $this->assertSameUsage($phpcsFile, $stackPtr);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function assertSameUsage(File $phpcsFile, int $stackPtr): void
    {
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
            if ($tokenContent !== 'assertSame') {
                continue;
            }

            $openingBraceIndex = $phpcsFile->findNext(T_WHITESPACE, ($i + 3), null, true);
            if (!$openingBraceIndex) {
                continue;
            }

            $nextIndex = $phpcsFile->findNext(T_WHITESPACE, ($openingBraceIndex + 1), null, true);
            if (!$nextIndex || !in_array($tokens[$nextIndex]['content'], static::$primitives, true)) {
                continue;
            }

            $primitive = strtolower($tokens[$nextIndex]['content']);
            $assert = 'assert' . ucfirst($primitive);
            $fix = $phpcsFile->addFixableError(sprintf('%s() used, expected %s()', static::METHOD_ASSERT_SAME, $assert), $i, 'InvalidAssert');
            if (!$fix) {
                continue;
            }

            $commaIndex = $phpcsFile->findNext(T_WHITESPACE, ($nextIndex + 1), null, true);
            $nextParamIndex = null;
            if ($commaIndex && $tokens[$commaIndex]['code'] === T_COMMA) {
                $nextParamIndex = $phpcsFile->findNext(T_WHITESPACE, ($commaIndex + 1), null, true);
            }

            $phpcsFile->fixer->beginChangeset();
            $phpcsFile->fixer->replaceToken($i + 2, $assert);
            $phpcsFile->fixer->replaceToken($nextIndex, '');
            if ($nextParamIndex) {
                for ($j = $nextIndex + 1; $j < $nextParamIndex; $j++) {
                    $phpcsFile->fixer->replaceToken($j, '');
                }
            }

            $phpcsFile->fixer->endChangeset();
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
