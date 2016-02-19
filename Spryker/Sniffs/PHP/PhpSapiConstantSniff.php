<?php
/**
 * (c) Spryker Systems GmbH copyright protected.
 */

namespace Spryker\Sniffs\PHP;

/**
 * Always use PHP_SAPI constant instead of php_sapi_name() method.
 */
class PhpSapiConstantSniff implements \PHP_CodeSniffer_Sniff
{

    const PHP_SAPI = 'PHP_SAPI';

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
        if (strtolower($tokenContent) !== 'php_sapi_name') {
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

        $closingBrace = $phpcsFile->findNext(T_WHITESPACE, ($openingBrace + 1), null, true);
        if (!$closingBrace || $tokens[$closingBrace]['type'] !== 'T_CLOSE_PARENTHESIS') {
            return;
        }

        $error = $tokenContent . '() found, should be const ' . self::PHP_SAPI . '.';
        $fix = $phpcsFile->addFixableError($error, $stackPtr);
        if ($fix) {
            $phpcsFile->fixer->replaceToken($stackPtr, self::PHP_SAPI);
            for ($i = $openingBrace; $i <= $closingBrace; ++$i) {
                $phpcsFile->fixer->replaceToken($i, '');
            }
        }
    }

}
