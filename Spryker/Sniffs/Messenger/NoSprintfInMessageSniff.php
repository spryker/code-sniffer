<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Messenger;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Zed messenger messages auto-translate; a pre-formatted sprintf() string can never
 * match a translation key. Pass the translation key and its parameters instead.
 */
class NoSprintfInMessageSniff extends AbstractSprykerSniff
{
    protected const string CODE_SPRINTF_IN_MESSAGE = 'SprintfInMessage';

    protected const string MESSAGE_SPRINTF_IN_MESSAGE = 'Do not pass sprintf() output to %s(); pass the translation key + parameters, never a pre-formatted string.';

    /**
     * @var array<string>
     */
    protected const array MESSENGER_METHODS = ['addSuccessMessage', 'addErrorMessage', 'addInfoMessage'];

    protected const string SPRINTF_FUNCTION = 'sprintf';

    protected const string APPLICATION_ZED = 'Zed';

    protected const string LAYER_COMMUNICATION = 'Communication';

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
    public function process(File $phpcsFile, $stackPtr)
    {
        if (!$this->isZedCommunication($phpcsFile)) {
            return $phpcsFile->numTokens;
        }

        $tokens = $phpcsFile->getTokens();
        $methodName = $tokens[$stackPtr]['content'];
        if (!in_array($methodName, static::MESSENGER_METHODS, true)) {
            return null;
        }

        $operatorPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
        if ($operatorPtr === false || $tokens[$operatorPtr]['code'] !== T_OBJECT_OPERATOR) {
            return null;
        }

        $openParenPtr = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);
        if ($openParenPtr === false || $tokens[$openParenPtr]['code'] !== T_OPEN_PARENTHESIS) {
            return null;
        }

        $firstArgumentPtr = $phpcsFile->findNext(Tokens::$emptyTokens, $openParenPtr + 1, null, true);
        if ($firstArgumentPtr === false) {
            return null;
        }

        if ($tokens[$firstArgumentPtr]['code'] === T_NS_SEPARATOR) {
            $firstArgumentPtr = $phpcsFile->findNext(Tokens::$emptyTokens, $firstArgumentPtr + 1, null, true);
            if ($firstArgumentPtr === false) {
                return null;
            }
        }

        if (
            $tokens[$firstArgumentPtr]['code'] !== T_STRING
            || strtolower($tokens[$firstArgumentPtr]['content']) !== static::SPRINTF_FUNCTION
        ) {
            return null;
        }

        $callParenPtr = $phpcsFile->findNext(Tokens::$emptyTokens, $firstArgumentPtr + 1, null, true);
        if ($callParenPtr === false || $tokens[$callParenPtr]['code'] !== T_OPEN_PARENTHESIS) {
            return null;
        }

        $phpcsFile->addError(
            static::MESSAGE_SPRINTF_IN_MESSAGE,
            $firstArgumentPtr,
            static::CODE_SPRINTF_IN_MESSAGE,
            [$methodName],
        );

        return null;
    }

    protected function isZedCommunication(File $phpcsFile): bool
    {
        $className = $this->getClassName($phpcsFile);
        if ($className === '') {
            return false;
        }

        $parts = explode('\\', $className);
        $application = $parts[1] ?? '';
        $layer = $parts[3] ?? '';

        return $application === static::APPLICATION_ZED && $layer === static::LAYER_COMMUNICATION;
    }
}
