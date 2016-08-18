<?php

/**
 * (c) Spryker Systems GmbH copyright protected.
 */
namespace Spryker\Sniffs\PHP;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Sniff;

/**
 * Casts should only be used in their short form.
 */
class ShortCastSniff implements PHP_CodeSniffer_Sniff
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
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['content'] === '!') {
            $prevIndex = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
            if ($tokens[$prevIndex]['content'] !== '!') {
                return;
            }

            $fix = $phpcsFile->addFixableError('`!!` cast not allowed, use `(bool)`', $stackPtr);
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

        $fix = $phpcsFile->addFixableError($content . ' found, expected ' . static::$matching[$key], $stackPtr);
        if ($fix) {
            $phpcsFile->fixer->replaceToken($stackPtr, static::$matching[$key]);
        }
    }

}
