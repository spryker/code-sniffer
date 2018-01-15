<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
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
     * @var array
     */
    public static $matching = [
        '(boolean)' => '(bool)',
        '(integer)' => '(int)',
    ];

    /**
     * @inheritdoc
     */
    public function register()
    {
        return [T_BOOL_CAST, T_INT_CAST, T_BOOLEAN_NOT];
    }

    /**
     * @inheritdoc
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
