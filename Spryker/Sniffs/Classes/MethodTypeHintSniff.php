<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * In method return types the own class or interface must be references as self.
 *
 * @author Mark Scherer
 */
class MethodTypeHintSniff extends AbstractSprykerSniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_FUNCTION];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $openParenthesisIndex = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr + 1);
        $closeParenthesisIndex = $tokens[$openParenthesisIndex]['parenthesis_closer'];

        $colonIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $closeParenthesisIndex + 1, null, true);
        if (!$colonIndex) {
            return;
        }

        $startIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $colonIndex + 1, $colonIndex + 3, true);
        if (!$startIndex) {
            return;
        }

        $lastIndex = null;
        $j = $startIndex;
        $extractedUseStatement = '';
        while (true) {
            if (!$this->isGivenKind([T_NS_SEPARATOR, T_STRING, T_RETURN_TYPE], $tokens[$j])) {
                break;
            }

            $lastIndex = $j;
            $extractedUseStatement .= $tokens[$j]['content'];
            ++$j;
        }

        if ($lastIndex === null) {
            return;
        }

        $extractedClassName = ltrim($extractedUseStatement, '\\');
        if ($extractedClassName !== $this->getCurrentClassName($phpcsFile)) {
            return;
        }

        $fix = $phpcsFile->addFixableError('Own class/interface should be referred to as "self".', $startIndex, 'TypeHint.Wrong.Self');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->replaceToken($startIndex, 'self');
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getCurrentClassName(File $phpCsFile): string
    {
        $fullClassName = parent::getClassName($phpCsFile);

        return substr($fullClassName, strrpos($fullClassName, '\\') + 1);
    }
}
