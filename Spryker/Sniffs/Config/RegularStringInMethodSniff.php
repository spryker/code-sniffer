<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Config;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Spryker Config methods may not return regular string.
 *
 * Note: This sniff will only run on Spryker Core files.
 */
class RegularStringInMethodSniff extends AbstractSprykerSniff
{
    protected const RETURN_INLINE_STRING_PATTERN = '/^return (\'[^\']+\')|("[^"]+");$/';

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_FUNCTION,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        if ($this->isConfig($phpCsFile) && $this->isSprykerNamespace($phpCsFile) && $this->isInlineStringReturned($phpCsFile, $stackPointer)) {
            $classMethod = $this->getClassMethod($phpCsFile, $stackPointer);
            $phpCsFile->addError(
                $classMethod . ' returns regular string. Fix this by extract a const.',
                $stackPointer,
                'OnlyOneNewAllowed'
            );
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return string
     */
    protected function getMethodName(File $phpCsFile, int $stackPointer): string
    {
        $tokens = $phpCsFile->getTokens();
        $methodNamePosition = $phpCsFile->findNext(T_STRING, $stackPointer);

        return $tokens[$methodNamePosition]['content'];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isConfig(File $phpCsFile): bool
    {
        $className = $this->getClassName($phpCsFile);

        return (substr($className, -6) === 'Config');
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isInlineStringReturned(File $phpCsFile, int $stackPointer): bool
    {
        $tokens = $phpCsFile->getTokens();

        $currentPosition = $phpCsFile->findNext(T_OPEN_CURLY_BRACKET, $stackPointer);
        $closePointer = $phpCsFile->findNext([T_PUBLIC, T_PROTECTED, T_PRIVATE], $currentPosition);

        while (true) {
            $currentPosition = $phpCsFile->findNext(T_RETURN, $currentPosition, $closePointer);

            if ($currentPosition === false) {
                break;
            }

            $endOfLinePointer = $phpCsFile->findEndOfStatement($currentPosition);

            $statementTokens = array_slice($tokens, $currentPosition, $endOfLinePointer - $currentPosition + 1);

            $stringStatement = $this->getTokensContent($statementTokens);

            if (preg_match(static::RETURN_INLINE_STRING_PATTERN, $stringStatement)) {
                return true;
            }

            $currentPosition = $endOfLinePointer;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return string
     */
    protected function getClassMethod(File $phpCsFile, int $stackPointer): string
    {
        return $this->getClassName($phpCsFile) . '::' . $this->getMethodName($phpCsFile, $stackPointer);
    }

    /**
     * @param array $tokens
     *
     * @return string
     */
    protected function getTokensContent(array $tokens): string
    {
        $statement = '';
        foreach ($tokens as $token) {
            $statement .= $token['content'];
        }

        return $statement;
    }
}
