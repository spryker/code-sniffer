<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode;
use RuntimeException;
use SlevomatCodingStandard\Helpers\DocCommentHelper;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Traits\CommentingTrait;

/**
 * Checks for missing/superfluous `|null` in docblock return annotations.
 */
class DocBlockReturnNullableTypeSniff extends AbstractSprykerSniff
{
    use CommentingTrait;

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
    public function process(File $phpCsFile, $stackPointer): void
    {
        $returnType = FunctionHelper::findReturnTypeHint($phpCsFile, $stackPointer);
        if ($returnType === null) {
            return;
        }

        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);
        if (!$docBlockEndIndex) {
            return;
        }

        $tokens = $phpCsFile->getTokens();
        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        $docBlockReturnIndex = $this->findDocBlockReturn($phpCsFile, $docBlockStartIndex, $docBlockEndIndex);
        if (!$docBlockReturnIndex) {
            return;
        }

        $nextIndex = $phpCsFile->findNext(T_DOC_COMMENT_STRING, $docBlockReturnIndex + 1, $docBlockEndIndex);
        if (!$nextIndex) {
            return;
        }

        $docBlockReturnTypes = $this->parseDocBlockReturnTypes($phpCsFile, $nextIndex);
        if ($docBlockReturnTypes === null) {
            return;
        }

        if ($returnType->isNullable()) {
            $this->assertRequiredNullableReturnType($phpCsFile, $stackPointer, $docBlockReturnTypes);

            return;
        }

        if ($returnType->getTypeHint() === 'mixed') {
            return;
        }

        $this->assertNotNullableReturnType($phpCsFile, $stackPointer, $docBlockReturnTypes);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return array<string>|null
     */
    protected function parseDocBlockReturnTypes(File $phpCsFile, int $stackPointer): ?array
    {
        $tokens = $phpCsFile->getTokens();

        $content = $tokens[$stackPointer]['content'];
        /** @var \PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode $valueNode */
        $valueNode = static::getValueNode($tokens[$stackPointer - 2]['content'], $content);
        if ($valueNode instanceof InvalidTagValueNode || $valueNode instanceof TypelessParamTagValueNode) {
            return [];
        }

        return $this->valueNodeParts($valueNode);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return int|null
     */
    protected function findDocBlockReturn(File $phpcsFile, int $docBlockStartIndex, int $docBlockEndIndex): ?int
    {
        $tokens = $phpcsFile->getTokens();

        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if (!$this->isGivenKind(T_DOC_COMMENT_TAG, $tokens[$i])) {
                continue;
            }
            if ($tokens[$i]['content'] !== '@return') {
                continue;
            }

            return $i;
        }

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     * @param array<string> $docBlockReturnTypes
     *
     * @return void
     */
    public function assertNotNullableReturnType(File $phpCsFile, int $stackPointer, array $docBlockReturnTypes): void
    {
        if (!$docBlockReturnTypes) {
            return;
        }
        if (!in_array('null', $docBlockReturnTypes, true)) {
            return;
        }

        $errorMessage = 'Method should not have `null` in return type in doc block.';
        $fix = $phpCsFile->addFixableError($errorMessage, $stackPointer, 'ReturnNullableInvalid');

        if (!$fix) {
            return;
        }

        $this->removeNullFromDocBlockReturnType($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     * @param array<string> $docBlockReturnTypes
     *
     * @return void
     */
    public function assertRequiredNullableReturnType(
        File $phpCsFile,
        int $stackPointer,
        array $docBlockReturnTypes
    ): void {
        if (!$docBlockReturnTypes) {
            return;
        }
        if (in_array('null', $docBlockReturnTypes, true)) {
            return;
        }

        $errorMessage = 'Method does not have `null` in return type in doc block.';
        $fix = $phpCsFile->addFixableError($errorMessage, $stackPointer, 'ReturnNullableMissing');

        if (!$fix) {
            return;
        }

        $this->addNullToDocBlockReturnType($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function addNullToDocBlockReturnType(File $phpCsFile, int $stackPointer): void
    {
        $returnTypeToken = $this->getDocBlockReturnTypeToken($phpCsFile, $stackPointer);

        $tokenIndex = $returnTypeToken['index'];
        $returnTypes = $returnTypeToken['token']['content'];
        $returnTypes = trim($returnTypes, '|') . '|null';

        $phpCsFile->fixer->beginChangeset();
        $phpCsFile->fixer->replaceToken($tokenIndex, $returnTypes);
        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function removeNullFromDocBlockReturnType(File $phpCsFile, int $stackPointer): void
    {
        $returnTypesToken = $this->getDocBlockReturnTypeToken($phpCsFile, $stackPointer);

        $tokenIndex = $returnTypesToken['index'];
        $returnTypes = explode('|', $returnTypesToken['token']['content']);
        foreach ($returnTypes as $key => $returnType) {
            if ($returnType === 'null') {
                unset($returnTypes[$key]);
            }
        }

        $phpCsFile->fixer->beginChangeset();
        $phpCsFile->fixer->replaceToken($tokenIndex, implode('|', $returnTypes));
        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @throws \RuntimeException
     *
     * @return array<string, mixed>
     */
    protected function getDocBlockReturnTypeToken(File $phpCsFile, int $stackPointer): array
    {
        $tokens = $phpCsFile->getTokens();

        $docBlockStartIndex = DocCommentHelper::findDocCommentOpenPointer($phpCsFile, $stackPointer);
        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);

        for ($i = $docBlockEndIndex; $i >= $docBlockStartIndex; $i--) {
            if ($tokens[$i]['content'] !== '@return') {
                continue;
            }

            $returnTypesTokenIndex = $phpCsFile->findNext(
                [T_DOC_COMMENT_WHITESPACE],
                $i + 1,
                null,
                true,
            );

            return [
                'tagIndex' => $i,
                'index' => $returnTypesTokenIndex,
                'token' => $tokens[$returnTypesTokenIndex],
            ];
        }

        throw new RuntimeException('No token found.');
    }
}
