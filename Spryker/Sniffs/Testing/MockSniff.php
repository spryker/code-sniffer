<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Testing;

use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use SlevomatCodingStandard\Helpers\ReturnTypeHint;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Ensures no wrong usage of MockObject return typehint or docblock return annotation.
 *
 * @author Mark Scherer
 * @license MIT
 */
class MockSniff extends AbstractSprykerSniff
{
    protected const MOCK_OBJECT = '\PHPUnit\Framework\MockObject\MockObject';

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
        if (!$this->isTest($phpcsFile, $stackPtr)) {
            return;
        }

        $returnTypeHint = FunctionHelper::findReturnTypeHint($phpcsFile, $stackPtr);
        $docBlockReturnTypes = $this->getDocBlockReturnTypes($phpcsFile, $stackPtr);

        $this->assertDocBlockReturnAnnotation($phpcsFile, $stackPtr, $docBlockReturnTypes, $returnTypeHint);
        $this->assertNoReturnTypehint($phpcsFile, $stackPtr, $returnTypeHint, $docBlockReturnTypes);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     * @param \SlevomatCodingStandard\Helpers\ReturnTypeHint|null $returnTypeHint
     * @param string[] $docBlockReturnTypes
     *
     * @return void
     */
    protected function assertNoReturnTypehint(
        File $phpcsFile,
        int $stackPtr,
        ?ReturnTypeHint $returnTypeHint,
        array $docBlockReturnTypes
    ): void {
        if (!$returnTypeHint || $returnTypeHint->getTypeHint() !== 'MockObject') {
            return;
        }

        $fix = $phpcsFile->addFixableError('MockObject must not be typehinted.', $returnTypeHint->getStartPointer(), 'InvalidTypehint');
        if (!$fix) {
            return;
        }

        $this->removeReturnTypeHint($phpcsFile, $stackPtr, $returnTypeHint);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     * @param \SlevomatCodingStandard\Helpers\ReturnTypeHint $returnTypeHint
     *
     * @return void
     */
    protected function removeReturnTypeHint(File $phpcsFile, int $stackPtr, ReturnTypeHint $returnTypeHint): void
    {
        $colonPointer = $phpcsFile->findPrevious(T_COLON, $returnTypeHint->getStartPointer(), $stackPtr);
        if (!$colonPointer) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        $endPtr = $returnTypeHint->getEndPointer();

        for ($i = $colonPointer; $i <= $endPtr; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     * @param string[] $docBlockReturnTypes
     * @param \SlevomatCodingStandard\Helpers\ReturnTypeHint|null $returnTypeHint
     *
     * @return void
     */
    protected function assertDocBlockReturnAnnotation(
        File $phpcsFile,
        int $stackPtr,
        array $docBlockReturnTypes,
        ?ReturnTypeHint $returnTypeHint
    ): void {
        $hasMockAnnotation = $this->hasMockObjectAnnotation($docBlockReturnTypes);

        if (!$hasMockAnnotation && $returnTypeHint && $returnTypeHint->getTypeHint() === 'MockObject') {
            $fix = $phpcsFile->addFixableError('Return typehint suggests missing return type annotation `' . static::MOCK_OBJECT . '`.', $stackPtr, 'MissingMockReturnType');
            if (!$fix) {
                return;
            }

            $contentIndex = $this->getDocBlockReturnTypeContentIndex($phpcsFile, $stackPtr);
            if ($contentIndex === null) {
                return;
            }

            $tokens = $phpcsFile->getTokens();
            $content = $tokens[$contentIndex]['content'];
            $newContent = $content . '|' . static::MOCK_OBJECT;

            $phpcsFile->fixer->beginChangeset();

            $phpcsFile->fixer->replaceToken($contentIndex, $newContent);

            $phpcsFile->fixer->endChangeset();

            return;
        }

        if ($hasMockAnnotation && $returnTypeHint && $returnTypeHint->getTypeHint() !== 'MockObject' && count($docBlockReturnTypes) > 2) {
            $phpcsFile->addError('Return typehint must not be used for complex mocks.', $stackPtr, 'InvalidReturnType');

            return;
        }

        if ($hasMockAnnotation && count($docBlockReturnTypes) < 2) {
            $phpcsFile->addError('Return type annotation is missing the class that is mocked.', $stackPtr, 'MissingReturnType');

            return;
        }

        if ($hasMockAnnotation && !$returnTypeHint && count($docBlockReturnTypes) === 2) {
            $returnType = null;
            foreach ($docBlockReturnTypes as $docBlockReturnType) {
                if (strpos($docBlockReturnType, 'MockObject') !== false) {
                    continue;
                }

                $returnType = $docBlockReturnType;
            }

            if (!$returnType || substr($returnType, -5) === 'Trait') {
                return;
            }

            $fix = $phpcsFile->addFixableError('Return typehint `' . $returnType . '` is missing.', $stackPtr, 'MissingTypeHint');
            if (!$fix) {
                return;
            }

            $this->addReturnTypeHint($phpcsFile, $stackPtr, $returnType);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     * @param string $returnTypeHint
     *
     * @return void
     */
    protected function addReturnTypeHint(File $phpcsFile, int $stackPtr, string $returnTypeHint): void
    {
        $tokens = $phpcsFile->getTokens();

        $closeParenthesisPointer = $tokens[$stackPtr]['parenthesis_closer'];
        if (!$closeParenthesisPointer) {
            return;
        }

        $content = $tokens[$closeParenthesisPointer]['content'];
        $content .= ': ' . $returnTypeHint;

        $phpcsFile->fixer->beginChangeset();

        $phpcsFile->fixer->replaceToken($closeParenthesisPointer, $content);

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return bool
     */
    protected function isTest(File $phpcsFile, int $stackPtr): bool
    {
        $filename = $phpcsFile->getFilename();
        if (substr($filename, -8) !== 'Test.php' && substr($filename, -9) !== 'Mocks.php') {
            return false;
        }

        return true;
    }

    /**
     * @param array $docBlockReturnTypes
     *
     * @return bool
     */
    protected function hasMockObjectAnnotation(array $docBlockReturnTypes): bool
    {
        foreach ($docBlockReturnTypes as $docBlockReturnType) {
            if (strpos($docBlockReturnType, 'MockObject') === false) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return int|null
     */
    protected function getDocBlockReturnTypeContentIndex(File $phpcsFile, int $stackPtr): ?int
    {
        $docBlockEndIndex = $this->findRelatedDocBlock($phpcsFile, $stackPtr);
        if (!$docBlockEndIndex) {
            return null;
        }

        $tokens = $phpcsFile->getTokens();
        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if ($tokens[$i]['content'] !== '@return') {
                continue;
            }

            $nextToken = $phpcsFile->findNext(T_DOC_COMMENT_STRING, $i, $docBlockEndIndex);

            return $nextToken ?: null;
        }

        return null;
    }
}
