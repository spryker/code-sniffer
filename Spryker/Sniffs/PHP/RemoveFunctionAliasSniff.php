<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Do not use aliases or long forms of functions.
 */
class RemoveFunctionAliasSniff implements Sniff
{
    /**
     * @see http://php.net/manual/en/aliases.php
     *
     * @var array<string>
     */
    public static $matching = [
        'is_integer' => 'is_int',
        'is_long' => 'is_int',
        'is_real' => 'is_float',
        'is_double' => 'is_float',
        'is_writeable' => 'is_writable',
        'join' => 'implode',
        'key_exists' => 'array_key_exists', // Deprecated function
        'sizeof' => 'count',
        'strchr' => 'strstr',
        'ini_alter' => 'ini_set',
        'fputs' => 'fwrite',
        'die' => 'exit',
        'chop' => 'rtrim',
        'print' => 'echo',
    ];

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_STRING];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $this->checkFixableAliases($phpcsFile, $stackPtr);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkFixableAliases(File $phpcsFile, int $stackPtr): void
    {
        $wrongTokens = [T_FUNCTION, T_OBJECT_OPERATOR, T_NEW, T_DOUBLE_COLON];

        $tokens = $phpcsFile->getTokens();

        $tokenContent = $tokens[$stackPtr]['content'];
        $key = strtolower($tokenContent);
        if (!isset(static::$matching[$key])) {
            return;
        }

        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if (!$previous || in_array($tokens[$previous]['code'], $wrongTokens, true)) {
            return;
        }

        $openingBrace = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if (!$openingBrace || $tokens[$openingBrace]['type'] !== 'T_OPEN_PARENTHESIS') {
            return;
        }

        $error = 'Function name ' . $tokenContent . '() found, should be ' . static::$matching[$key] . '().';
        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'LongInvalid');
        if ($fix) {
            $phpcsFile->fixer->replaceToken($stackPtr, static::$matching[$key]);
        }
    }
}
