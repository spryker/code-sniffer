<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Helpers\DocCommentHelper;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Checks for missing |null in docblock return annotations.
 */
class DocBlockReturnNullableTypeSniff extends AbstractSprykerSniff
{
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
        $errorMessage = 'Method does not have a return `null` typehint in doc block.';

        $returnType = FunctionHelper::findReturnTypeHint($phpCsFile, $stackPointer);

        if ($returnType === null) {
            return;
        }

        if (!$returnType->isNullable()) {
            return;
        }

        $docBlockReturnTypes = $this->getDocBlockReturnTypes($phpCsFile, $stackPointer);

        if ($docBlockReturnTypes === []) {
            return;
        }

        if (in_array('null', $docBlockReturnTypes, true)) {
            return;
        }

        $fixable = $phpCsFile->addFixableError($errorMessage, $stackPointer, 'ReturnNullableMissing');

        if (!$fixable) {
            return;
        }

        $this->fixDocBlockReturnType($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function fixDocBlockReturnType(File $phpCsFile, int $stackPointer): void
    {
        $returnTypesToken = $this->getDocBlockReturnTypesToken($phpCsFile, $stackPointer);

        $tokenIndex = $returnTypesToken['index'];
        $returnTypes = $returnTypesToken['token']['content'];
        $returnTypes = trim($returnTypes, '|') . '|null';

        $phpCsFile->fixer->beginChangeset();
        $phpCsFile->fixer->replaceToken($tokenIndex, $returnTypes);
        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return array
     */
    protected function getDocBlockReturnTypesToken(File $phpCsFile, int $stackPointer): array
    {
        $tokens = $phpCsFile->getTokens();

        $docBlockStartIndex = DocCommentHelper::findDocCommentOpenToken($phpCsFile, $stackPointer);
        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);

        for ($i = $docBlockEndIndex; $i >= $docBlockStartIndex; $i--) {
            if ($tokens[$i]['content'] !== '@return') {
                continue;
            }

            $returnTypesTokenIndex = $phpCsFile->findNext(
                [T_DOC_COMMENT_WHITESPACE],
                $i + 1,
                null,
                true
            );

            return [
                'index' => $returnTypesTokenIndex,
                'token' => $tokens[$returnTypesTokenIndex],
            ];
        }
    }
}
