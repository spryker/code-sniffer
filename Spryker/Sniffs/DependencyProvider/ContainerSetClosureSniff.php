<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\DependencyProvider;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * DependencyProviders must register every dependency through a late-binding closure.
 *
 * The second argument of `$container->set()` must be a closure or arrow function so the
 * dependency is only resolved when it is actually used.
 */
class ContainerSetClosureSniff extends AbstractSprykerSniff
{
    /**
     * @var string
     */
    protected const CODE_SET_WITHOUT_CLOSURE = 'SetWithoutClosure';

    /**
     * @var string
     */
    protected const MESSAGE_SET_WITHOUT_CLOSURE = 'The second argument of $container->set() must be a closure or arrow function; wrap dependencies in a closure for late binding.';

    /**
     * @var string
     */
    protected const CONTAINER_VARIABLE = '$container';

    /**
     * @var string
     */
    protected const SET_METHOD = 'set';

    /**
     * @var string
     */
    protected const DEPENDENCY_PROVIDER_SUFFIX = 'DependencyProvider';

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
        if (!$this->isDependencyProvider($phpcsFile)) {
            return $phpcsFile->numTokens;
        }

        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['content'] !== static::SET_METHOD) {
            return null;
        }

        $operatorPtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
        if ($operatorPtr === false || $tokens[$operatorPtr]['code'] !== T_OBJECT_OPERATOR) {
            return null;
        }

        $variablePtr = $phpcsFile->findPrevious(Tokens::$emptyTokens, $operatorPtr - 1, null, true);
        if (
            $variablePtr === false
            || $tokens[$variablePtr]['code'] !== T_VARIABLE
            || $tokens[$variablePtr]['content'] !== static::CONTAINER_VARIABLE
        ) {
            return null;
        }

        $openParenPtr = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);
        if (
            $openParenPtr === false
            || $tokens[$openParenPtr]['code'] !== T_OPEN_PARENTHESIS
            || !isset($tokens[$openParenPtr]['parenthesis_closer'])
        ) {
            return null;
        }

        $secondArgumentPtr = $this->findSecondArgumentStart($phpcsFile, $openParenPtr);
        if ($secondArgumentPtr === null) {
            return null;
        }

        if ($this->isClosureOrArrowFunction($phpcsFile, $secondArgumentPtr)) {
            return null;
        }

        $phpcsFile->addError(
            static::MESSAGE_SET_WITHOUT_CLOSURE,
            $secondArgumentPtr,
            static::CODE_SET_WITHOUT_CLOSURE,
        );

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     *
     * @return bool
     */
    protected function isDependencyProvider(File $phpcsFile): bool
    {
        $className = $this->getClassName($phpcsFile);
        if ($className === '') {
            return false;
        }

        $parts = explode('\\', $className);
        $shortName = (string)end($parts);

        return substr($shortName, -strlen(static::DEPENDENCY_PROVIDER_SUFFIX)) === static::DEPENDENCY_PROVIDER_SUFFIX;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $openParenPtr
     *
     * @return int|null
     */
    protected function findSecondArgumentStart(File $phpcsFile, int $openParenPtr): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $closeParenPtr = $tokens[$openParenPtr]['parenthesis_closer'];

        for ($i = $openParenPtr + 1; $i < $closeParenPtr; $i++) {
            if ($tokens[$i]['code'] === T_OPEN_PARENTHESIS && isset($tokens[$i]['parenthesis_closer'])) {
                $i = $tokens[$i]['parenthesis_closer'];

                continue;
            }

            if (isset($tokens[$i]['bracket_closer']) && $tokens[$i]['bracket_closer'] > $i) {
                $i = $tokens[$i]['bracket_closer'];

                continue;
            }

            if ($tokens[$i]['code'] !== T_COMMA) {
                continue;
            }

            $argumentPtr = $phpcsFile->findNext(Tokens::$emptyTokens, $i + 1, $closeParenPtr, true);

            return $argumentPtr === false ? null : $argumentPtr;
        }

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $argumentPtr
     *
     * @return bool
     */
    protected function isClosureOrArrowFunction(File $phpcsFile, int $argumentPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $code = $tokens[$argumentPtr]['code'];

        if ($code === T_CLOSURE || $code === T_FN) {
            return true;
        }

        if ($code !== T_STATIC) {
            return false;
        }

        $nextPtr = $phpcsFile->findNext(Tokens::$emptyTokens, $argumentPtr + 1, null, true);
        if ($nextPtr === false) {
            return false;
        }

        return $tokens[$nextPtr]['code'] === T_CLOSURE || $tokens[$nextPtr]['code'] === T_FN;
    }
}
