<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Sniffs\AbstractSniffs;

use PHP_CodeSniffer\Files\File;

abstract class AbstractMethodSignatureSniff extends AbstractClassDetectionSprykerSniff
{
    protected const PARAM_DEPRECATED = '@deprecated';
    protected const KEY_CONTENT = 'content';

    protected const CODE_METHOD_MUST_BE_NULLABLE = 'MethodMustBeNullable';
    protected const CODE_METHOD_MUST_NOT_BE_NULLABLE = 'MethodMustNotBeNullable';

    protected const MESSAGE_PATTERN_METHOD_MUST_BE_NULLABLE = 'The method %s() return type must be Nullable. Please change the signature.';
    protected const MESSAGE_PATTERN_METHOD_MUST_NOT_BE_NULLABLE = 'The method %s() return type mustn\'t be Nullable. Please change the signature.';

    protected const NAME_PREFIX_METHOD_FIND = 'find';
    protected const NAME_PREFIX_METHOD_GET = 'get';

    protected const OFFSET_TO_NEXT_SINGLE_CHAR_TOKEN = 3;

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_CLASS,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        if (!$this->getSnifferIsApplicable($phpCsFile, $stackPointer)) {
            return;
        }

        $this->runSniffer($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function runSniffer(File $phpCsFile, int $stackPointer): void
    {
        $i = $stackPointer;
        $tokens = $phpCsFile->getTokens();

        while ($i) {
            $methodPointer = $phpCsFile->findNext([T_FUNCTION], $i);


            if (!$methodPointer) {
                break;
            }

            $i = $methodPointer + 3;

            if ($this->hasMethodDeprecated($phpCsFile, $methodPointer, $tokens)) {
                continue;
            }

            $this->checkFindAndGetMethods($phpCsFile, $methodPointer, $tokens);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     * @param array $tokens
     *
     * @return void
     */
    protected function checkFindAndGetMethods(File $phpCsFile, int $methodPointer, array $tokens): void
    {
        $methodName = $this->getMethodName($phpCsFile, $methodPointer, $tokens);

        if (strpos($methodName, static::NAME_PREFIX_METHOD_FIND) !== 0
            && strpos($methodName, static::NAME_PREFIX_METHOD_GET) !== 0
        ) {
            return;
        }

        $methodColonPoinetr = $this->getMethodColonPointer($phpCsFile, $methodPointer, $tokens);

        if (!$methodColonPoinetr) {
            return;
        }

        $methodNullableTokenPointer =  $this->isMethodNullable($phpCsFile, $methodColonPoinetr, $tokens);

        if (strpos($methodName, static::NAME_PREFIX_METHOD_FIND) === 0 && !$methodNullableTokenPointer) {
            $phpCsFile->addError(
                sprintf(static::MESSAGE_PATTERN_METHOD_MUST_BE_NULLABLE, $methodName),
                $methodPointer,
                static::CODE_METHOD_MUST_BE_NULLABLE
            );
        }

        if (strpos($methodName, static::NAME_PREFIX_METHOD_GET) === 0 && $methodNullableTokenPointer) {
            $phpCsFile->addError(
                sprintf(static::MESSAGE_PATTERN_METHOD_MUST_NOT_BE_NULLABLE, $methodName),
                $methodPointer,
                static::CODE_METHOD_MUST_NOT_BE_NULLABLE
            );
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    abstract protected function getSnifferIsApplicable(File $phpCsFile, int $stackPointer): bool;

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $methodPointer
     *
     * @return bool
     */
    protected function hasMethodDeprecated(File $phpCsFile, int $methodPointer, array $tokens): bool
    {
        $methodPhpDocStartPointer = $phpCsFile->findPrevious([T_DOC_COMMENT_OPEN_TAG], $methodPointer);

        for ($i = $methodPhpDocStartPointer; $i < $methodPointer;) {
            $i = $phpCsFile->findNext([T_DOC_COMMENT_TAG], $i, $methodPointer);

            if ($tokens[$i][static::KEY_CONTENT] === static::PARAM_DEPRECATED) {
                return true;
            }

            if (!$i) {
                return false;
            }

            $i += static::OFFSET_TO_NEXT_SINGLE_CHAR_TOKEN;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $methodColonPointer
     * @param array $tokens
     *
     * @return bool
     */
    protected function isMethodNullable(File $phpCsFile, int $methodColonPointer, array $tokens): bool
    {
        return (bool) $phpCsFile->findNext(
            [T_NULLABLE],
            $methodColonPointer,
            $methodColonPointer + static::OFFSET_TO_NEXT_SINGLE_CHAR_TOKEN
        );
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $methodPointer
     * @param array $tokens
     *
     * @return mixed
     */
    protected function getMethodName(File $phpCsFile, int $methodPointer, array $tokens)
    {
        return $tokens[$phpCsFile->findNext([T_STRING], $methodPointer)][static::KEY_CONTENT];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $methodPointer
     *
     * @return int|false
     */
    protected function getMethodColonPointer(File $phpCsFile, int $methodPointer)
    {
        $methodCloseSquareBracket = $phpCsFile->findNext([T_CLOSE_PARENTHESIS], $methodPointer);
        $methodColonPointer = $phpCsFile->findNext(
            [T_COLON],
            $methodCloseSquareBracket,
            $methodCloseSquareBracket + static::OFFSET_TO_NEXT_SINGLE_CHAR_TOKEN
        );

        if (!$methodColonPointer) {
            return false;
        }

        return $methodColonPointer;
    }
}
