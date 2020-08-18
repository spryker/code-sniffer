<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Do not use exit functions without argument. Check aliasing.
 */
class ExitSniff implements Sniff
{
    /**
     * @see http://php.net/manual/en/aliases.php
     *
     * @var string[]
     */
    public static $aliases = [
        'die' => 'exit',
    ];

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_EXIT];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $this->checkExitUsage($phpcsFile, $stackPtr);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkExitUsage(File $phpcsFile, int $stackPtr): void
    {
        $wrongTokens = [T_FUNCTION, T_OBJECT_OPERATOR, T_NEW, T_DOUBLE_COLON];

        $tokens = $phpcsFile->getTokens();

        $tokenContent = $tokens[$stackPtr]['content'];
        $key = strtolower($tokenContent);
        if (isset(static::$aliases[$key])) {
            $this->fixAlias($phpcsFile, $stackPtr, $key);
        }

        $openingBrace = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if (!$openingBrace || $tokens[$openingBrace]['type'] !== 'T_OPEN_PARENTHESIS') {
            return;
        }

        if ($tokens[$openingBrace]['parenthesis_closer'] > $openingBrace + 1) {
            return;
        }

        $error = $key . '() without integer exit code argument is not allowed.';
        $phpcsFile->addError($error, $stackPtr, 'Invalid');
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     * @param string $before
     *
     * @return void
     */
    protected function fixAlias(File $phpcsFile, int $stackPtr, string $before): void
    {
        $after = static::$aliases[$before];
        $fix = $phpcsFile->addFixableError($before . '() should be ' . $after . '()', $stackPtr, 'Alias');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->replaceToken($stackPtr, $after);
    }
}
