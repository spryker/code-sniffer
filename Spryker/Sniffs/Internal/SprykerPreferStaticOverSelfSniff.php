<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Internal;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Checks if Spryker code uses "late static binding" inside methods.
 * Always use `static::` over `self::` usage here to allow extendability/customization.
 */
class SprykerPreferStaticOverSelfSniff extends AbstractSprykerSniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_FUNCTION,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpCsFile, $stackPointer): void
    {
        if (!$this->isCore($phpCsFile)) {
            return;
        }

        $this->assertStatic($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function assertStatic(File $phpCsFile, int $stackPointer): void
    {
        $tokens = $phpCsFile->getTokens();

        // We skip for interface methods
        if (empty($tokens[$stackPointer]['scope_opener']) || empty($tokens[$stackPointer]['scope_closer'])) {
            return;
        }

        $scopeOpener = $tokens[$stackPointer]['scope_opener'];
        $scopeCloser = $tokens[$stackPointer]['scope_closer'];

        for ($i = $scopeOpener; $i < $scopeCloser; $i++) {
            // We don't want to detect throws from nested scopes, so we'll just
            // skip those.
            if (in_array($tokens[$i]['code'], [T_FN, T_CLOSURE], true)) {
                $i = $tokens[$i]['scope_closer'];

                continue;
            }

            if ($tokens[$i]['code'] !== T_SELF) {
                continue;
            }

            $nextIndex = $phpCsFile->findNext(Tokens::$emptyTokens, $i + 1, null, true);
            if (!$nextIndex || $tokens[$nextIndex]['code'] !== T_DOUBLE_COLON) {
                continue;
            }

            $fix = $phpCsFile->addFixableError('Please use static:: instead of self::', $i, 'StaticVsSelf');
            if (!$fix) {
                return;
            }

            $phpCsFile->fixer->beginChangeset();
            $phpCsFile->fixer->replaceToken($i, 'static');
            $phpCsFile->fixer->endChangeset();
        }
    }
}
