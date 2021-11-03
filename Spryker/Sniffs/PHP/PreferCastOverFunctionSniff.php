<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Always use simple casts instead of method invocation.
 */
class PreferCastOverFunctionSniff extends AbstractSprykerSniff
{
    /**
     * @var array<string>
     */
    protected static $matching = [
        'strval' => 'string',
        'intval' => 'int',
        'floatval' => 'float',
        'boolval' => 'bool',
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
        $wrongTokens = [T_FUNCTION, T_OBJECT_OPERATOR, T_NEW, T_DOUBLE_COLON];

        $tokens = $phpcsFile->getTokens();

        $tokenContent = $tokens[$stackPtr]['content'];
        $key = strtolower($tokenContent);
        if (!isset(static::$matching[$key])) {
            return;
        }

        $previous = (int)$phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if (!$previous || in_array($tokens[$previous]['code'], $wrongTokens, true)) {
            return;
        }

        $openingBraceIndex = (int)$phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if (!$openingBraceIndex || $tokens[$openingBraceIndex]['type'] !== 'T_OPEN_PARENTHESIS') {
            return;
        }

        $closingBraceIndex = $tokens[$openingBraceIndex]['parenthesis_closer'];

        // We must ignore when commas are encountered
        if ($this->contains($phpcsFile, 'T_COMMA', $openingBraceIndex + 1, $closingBraceIndex - 1)) {
            return;
        }

        $error = $tokenContent . '() found, should be ' . static::$matching[$key] . ' cast.';

        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'MethodVsCast');
        if ($fix) {
            $this->fixContent($phpcsFile, $stackPtr, $key, $openingBraceIndex, $closingBraceIndex);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     * @param string $key
     * @param int $openingBraceIndex
     * @param int $closingBraceIndex
     *
     * @return void
     */
    protected function fixContent(
        File $phpcsFile,
        int $stackPtr,
        string $key,
        int $openingBraceIndex,
        int $closingBraceIndex
    ): void {
        $needsBrackets = $this->needsBrackets($phpcsFile, $openingBraceIndex, $closingBraceIndex);

        $cast = '(' . static::$matching[$key] . ')';

        $phpcsFile->fixer->replaceToken($stackPtr, $cast);
        if (!$needsBrackets) {
            $phpcsFile->fixer->replaceToken($openingBraceIndex, '');
            $phpcsFile->fixer->replaceToken($closingBraceIndex, '');
        }
    }
}
