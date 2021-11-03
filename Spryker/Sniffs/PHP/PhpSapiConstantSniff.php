<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Always use PHP_SAPI constant instead of php_sapi_name() function.
 */
class PhpSapiConstantSniff implements Sniff
{
    /**
     * @var string
     */
    protected const PHP_SAPI = 'PHP_SAPI';

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
        $tokens = $phpcsFile->getTokens();

        $wrongTokens = [T_FUNCTION, T_OBJECT_OPERATOR, T_NEW, T_DOUBLE_COLON];

        $tokenContent = $tokens[$stackPtr]['content'];
        if (strtolower($tokenContent) !== 'php_sapi_name') {
            return;
        }

        $previous = (int)$phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if (!$previous || in_array($tokens[$previous]['code'], $wrongTokens, true)) {
            return;
        }

        $openingBrace = (int)$phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if (!$openingBrace || $tokens[$openingBrace]['type'] !== 'T_OPEN_PARENTHESIS') {
            return;
        }

        $closingBrace = (int)$phpcsFile->findNext(T_WHITESPACE, ($openingBrace + 1), null, true);
        if (!$closingBrace || $tokens[$closingBrace]['type'] !== 'T_CLOSE_PARENTHESIS') {
            return;
        }

        $error = $tokenContent . '() found, should be const ' . static::PHP_SAPI . '.';
        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'MethodVsConst');
        if ($fix) {
            $phpcsFile->fixer->replaceToken($stackPtr, static::PHP_SAPI);
            for ($i = $openingBrace; $i <= $closingBrace; ++$i) {
                $phpcsFile->fixer->replaceToken($i, '');
            }
        }
    }
}
