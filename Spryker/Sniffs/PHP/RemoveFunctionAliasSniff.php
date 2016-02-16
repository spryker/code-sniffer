<?php
/**
 * (c) Spryker Systems GmbH copyright protected.
 */

namespace Spryker\Sniffs\PHP;

class RemoveFunctionAliasSniff implements \PHP_CodeSniffer_Sniff
{

    /**
     * @see http://php.net/manual/en/aliases.php
     *
     * @var array
     */
    public static $matching = [
        'is_integer' => 'is_int',
        'is_long' => 'is_int',
        'is_real' => 'is_float',
        'is_double' => 'is_float',
        'is_writeable' => 'is_writable',
        'join' => 'explode',
        'key_exists' => 'array_key_exists', // Deprecated function
        'sizeof' => 'count',
        'strchr' => 'strstr',
        'ini_alter' => 'ini_set',
        'fputs' => 'fwrite',
        'die' => 'exit',
        'chop' => 'rtrim',
        'print' => 'echo'
    ];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [T_STRING];
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

        $wrongTokens = [T_FUNCTION, T_OBJECT_OPERATOR, T_NEW, T_DOUBLE_COLON];

        $tokenContent = $tokens[$stackPtr]['content'];
        $key = strtolower($tokenContent);
        if (!isset(self::$matching[$key])) {
            return;
        }

        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if (!$previous || in_array($tokens[$previous]['code'], $wrongTokens)) {
            return;
        }

        $openingBrace = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if (!$openingBrace || $tokens[$openingBrace]['type'] !== 'T_OPEN_PARENTHESIS') {
            return;
        }

        $error = 'Function name ' . $tokenContent . '() found, should be ' . self::$matching[$key] . '().';
        $fix = $phpcsFile->addFixableError($error, $stackPtr);
        if ($fix) {
            $phpcsFile->fixer->replaceToken($stackPtr, self::$matching[$key]);
        }
    }

}
