<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Casts should only be used in their short form.
 */
class ShortCastSniff implements Sniff
{
    /**
     * @var string[]
     */
    public static $matching = [
        '(boolean)' => '(bool)',
        '(integer)' => '(int)',
    ];

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_BOOL_CAST, T_INT_CAST, T_BOOLEAN_NOT];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['content'] === '!') {
            $prevIndex = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
            if ($tokens[$prevIndex]['content'] !== '!') {
                return;
            }

            $fix = $phpcsFile->addFixableError('`!!` cast not allowed, use `(bool)`', $stackPtr, 'DoubleNotInvalid');
            if ($fix) {
                $phpcsFile->fixer->replaceToken($prevIndex, '');
                $phpcsFile->fixer->replaceToken($stackPtr, '(bool)');
            }

            return;
        }

        $content = $tokens[$stackPtr]['content'];
        $key = strtolower($content);

        if (!isset(static::$matching[$key])) {
            return;
        }

        $fix = $phpcsFile->addFixableError($content . ' found, expected ' . static::$matching[$key], $stackPtr, 'LongInvalid');
        if ($fix) {
            $phpcsFile->fixer->replaceToken($stackPtr, static::$matching[$key]);
        }
    }
}
