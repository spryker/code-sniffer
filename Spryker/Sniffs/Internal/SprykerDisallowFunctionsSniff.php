<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Internal;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Do not use functions that are not available for lowest version supported.
 *
 * Can be removed/disabled with use of symfony polyfills.
 */
class SprykerDisallowFunctionsSniff extends AbstractSprykerSniff
{
    /**
     * @var array<string>
     */
    protected static $disallowed = [
        // PHP 8.0 (https://github.com/symfony/polyfill-php80)
        'str_contains',
        'str_starts_with',
        'str_ends_with',
        'get_debug_type',
        'get_resource_id',
        'fdiv',
        'preg_last_error_msg',
        // PHP 8.1 (https://github.com/symfony/polyfill-php81)
        'array_is_list',
    ];

    /**
     * @var array<int>
     */
    protected static $wrongTokens = [T_FUNCTION, T_OBJECT_OPERATOR, T_NEW, T_DOUBLE_COLON];

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
        $this->checkForbiddenFunctions($phpcsFile, $stackPtr);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkForbiddenFunctions(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $tokenContent = $tokens[$stackPtr]['content'];
        $key = strtolower($tokenContent);
        if (!in_array($key, static::$disallowed, true)) {
            return;
        }

        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if (!$previous || in_array($tokens[$previous]['code'], static::$wrongTokens, true)) {
            return;
        }

        $openingBrace = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if (!$openingBrace || $tokens[$openingBrace]['type'] !== 'T_OPEN_PARENTHESIS') {
            return;
        }

        $error = $tokenContent . '() usage found. This function cannot be used in core yet.';
        $phpcsFile->addError($error, $stackPtr, 'Invalid');
    }
}
