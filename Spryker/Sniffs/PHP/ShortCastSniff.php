<?php

/**
 * (c) Spryker Systems GmbH copyright protected.
 */
namespace Spryker\Sniffs\PHP;

/**
 * Casts should only be used in their short form.
 */
class ShortCastSniff implements \PHP_CodeSniffer_Sniff
{

    /**
     * @var array
     */
    public static $matching = [
        '(boolean)' => '(bool)',
        '(integer)' => '(int)',
    ];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [T_BOOL_CAST, T_INT_CAST, T_BOOLEAN_NOT];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer_File $phpcsFile All the tokens found in the document.
     * @param int $stackPtr The position of the current token
     *    in the stack passed in $tokens.
     * @return void
     */
    public function process(\PHP_CodeSniffer_File $phpcsFile, $stackPtr)
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

        if (!isset(self::$matching[$key])) {
            return;
        }

        $fix = $phpcsFile->addFixableError($content . ' found, expected ' . self::$matching[$key], $stackPtr);
        if ($fix) {
            $phpcsFile->fixer->replaceToken($stackPtr, self::$matching[$key]);
        }
    }

}
