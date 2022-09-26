<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Traits\CommentingTrait;

/**
 * Ensures Doc Blocks for constants exist and are correct.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockConstSniff extends AbstractSprykerSniff
{
    use CommentingTrait;

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_CONST,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpCsFile, $stackPointer): void
    {
        $tokens = $phpCsFile->getTokens();

        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);
        if (!$docBlockEndIndex) {
            $defaultValueType = $this->findDefaultValueType($phpCsFile, $stackPointer);
            if ($defaultValueType === null) {
                // Let's ignore for now
                //$phpCsFile->addError('Doc Block for const missing', $stackPointer, 'VarDocBlockMissing');

                return;
            }

            if ($defaultValueType === 'null') {
                $phpCsFile->addError('Doc Block `@var` with type `...|' . $defaultValueType . '` for const missing', $stackPointer, 'VarDocBlockMissing');

                return;
            }

            $fix = $phpCsFile->addFixableError('Doc Block for const missing', $stackPointer, 'VarDocBlockMissing');
            if (!$fix) {
                return;
            }

            $this->addDocBlock($phpCsFile, $stackPointer, $defaultValueType);

            return;
        }

        /** @var int $docBlockStartIndex */
        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];
        if ($this->hasInheritDoc($phpCsFile, $docBlockStartIndex, $docBlockEndIndex)) {
            return;
        }

        $defaultValueType = $this->findDefaultValueType($phpCsFile, $stackPointer);

        $tagIndex = null;
        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if (!in_array($tokens[$i]['content'], ['@var', '@const'], true)) {
                continue;
            }

            $tagIndex = $i;
        }

        if (!$tagIndex) {
            $this->handleMissingVar($phpCsFile, $docBlockEndIndex, $docBlockStartIndex, $defaultValueType);

            return;
        }

        $typeIndex = $tagIndex + 2;

        if ($tokens[$typeIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
            $this->handleMissingVarType($phpCsFile, $tagIndex, $defaultValueType);

            return;
        }

        $tagIndexContent = $tokens[$tagIndex]['content'];
        $requiresTagUpdate = $tagIndexContent !== '@var';
        if ($requiresTagUpdate) {
            $fix = $phpCsFile->addFixableError(sprintf('Wrong tag used, expected `%s`, got `%s`', '@var', $tagIndexContent), $tagIndex, 'WrongTag');
            if ($fix) {
                $phpCsFile->fixer->beginChangeset();
                $phpCsFile->fixer->replaceToken($tagIndex, '@var');
                $phpCsFile->fixer->endChangeset();
            }
        }

        $content = $tokens[$typeIndex]['content'];
        if (!$content) {
            $error = 'Doc Block type for property annotation @var missing';
            if ($defaultValueType) {
                $error .= ', type `' . $defaultValueType . '` detected';
            }
            $phpCsFile->addError($error, $stackPointer, 'VarTypeEmpty');

            return;
        }

        if ($defaultValueType === null) {
            return;
        }

        /** @var \PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode $valueNode */
        $valueNode = static::getValueNode($tokens[$tagIndex]['content'], $content);
        if ($valueNode instanceof InvalidTagValueNode || $valueNode instanceof TypelessParamTagValueNode) {
            return;
        }
        $parts = $this->valueNodeParts($valueNode);

        if (in_array($defaultValueType, $parts, true)) {
            return;
        }
        if ($defaultValueType === 'array' && ($this->containsTypeArray($parts) || $this->containsTypeArray($parts, 'list'))) {
            return;
        }
        if ($defaultValueType === 'false' && in_array('bool', $parts, true)) {
            return;
        }

        if ($defaultValueType === 'false') {
            $defaultValueType = 'bool';
        }

        $fix = $phpCsFile->addFixableError('Doc Block type `' . $content . '` for property annotation @var incorrect, type `' . $defaultValueType . '` expected', $stackPointer, 'VarTypeIncorrect');
        if ($fix) {
            $newComment = trim(sprintf(
                '%s %s %s',
                implode('|', $parts),
                $valueNode->variableName,
                $valueNode->description,
            ));
            $phpCsFile->fixer->beginChangeset();
            $phpCsFile->fixer->replaceToken($typeIndex, $newComment);
            $phpCsFile->fixer->endChangeset();
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return string|null
     */
    protected function findDefaultValueType(File $phpCsFile, int $stackPointer): ?string
    {
        $tokens = $phpCsFile->getTokens();

        $nameIndex = $phpCsFile->findNext(Tokens::$emptyTokens, $stackPointer + 1, null, true);
        if (!$nameIndex || !$this->isGivenKind(T_STRING, $tokens[$nameIndex])) {
            return null;
        }

        $nextIndex = $phpCsFile->findNext(Tokens::$emptyTokens, $nameIndex + 1, null, true);
        if (!$nextIndex || !$this->isGivenKind(T_EQUAL, $tokens[$nextIndex])) {
            return null;
        }

        $nextIndex = $phpCsFile->findNext(Tokens::$emptyTokens, $nextIndex + 1, null, true);
        if (!$nextIndex) {
            return null;
        }

        return $this->detectType($tokens[$nextIndex]);
    }

    /**
     * @param array<string, mixed> $token
     *
     * @return string|null
     */
    protected function detectType(array $token): ?string
    {
        if ($this->isGivenKind(T_OPEN_SHORT_ARRAY, $token)) {
            return 'array';
        }

        if ($this->isGivenKind(T_LNUMBER, $token)) {
            return 'int';
        }

        if ($this->isGivenKind(T_CONSTANT_ENCAPSED_STRING, $token)) {
            return 'string';
        }

        if ($this->isGivenKind([T_TRUE], $token)) {
            return 'bool';
        }

        if ($this->isGivenKind([T_FALSE], $token)) {
            return 'false';
        }

        if ($this->isGivenKind(T_NULL, $token)) {
            return 'null';
        }

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockEndIndex
     * @param int $docBlockStartIndex
     * @param string|null $defaultValueType
     *
     * @return void
     */
    protected function handleMissingVar(
        File $phpCsFile,
        int $docBlockEndIndex,
        int $docBlockStartIndex,
        ?string $defaultValueType
    ): void {
        $error = 'Doc Block annotation @var for const missing';

        if ($defaultValueType === null) {
            // Let's skip for now for non-trivial cases
            //$phpCsFile->addError($error, $docBlockEndIndex, 'DocBlockMissing');

            return;
        }

        if ($defaultValueType === 'false') {
            $defaultValueType = 'bool';
        }

        $error .= ', type `' . $defaultValueType . '` detected';

        if ($defaultValueType === 'null') {
            $phpCsFile->addError($error, $docBlockEndIndex, 'TypeMissing');

            return;
        }

        $fix = $phpCsFile->addFixableError($error, $docBlockEndIndex, 'WrongType');
        if (!$fix) {
            return;
        }

        $index = $phpCsFile->findPrevious(T_DOC_COMMENT_WHITESPACE, $docBlockEndIndex - 1, $docBlockStartIndex, true);
        if (!$index) {
            $index = $docBlockStartIndex;
        }

        $whitespace = $this->getIndentationWhitespace($phpCsFile, $index);

        $phpCsFile->fixer->beginChangeset();
        $phpCsFile->fixer->addNewline($index);
        $phpCsFile->fixer->addContent($index, $whitespace . '* @var ' . $defaultValueType);
        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $varIndex
     * @param string|null $defaultValueType
     *
     * @return void
     */
    protected function handleMissingVarType(File $phpCsFile, int $varIndex, ?string $defaultValueType): void
    {
        $error = 'Doc Block type for property annotation @var missing';
        if ($defaultValueType === null) {
            $phpCsFile->addError($error, $varIndex, 'VarTypeMissing');

            return;
        }

        if ($defaultValueType === 'false') {
            $defaultValueType = 'bool';
        }

        $error .= ', type `' . $defaultValueType . '` detected';
        $fix = $phpCsFile->addFixableError($error, $varIndex, 'WrongType');
        if (!$fix) {
            return;
        }

        $phpCsFile->fixer->beginChangeset();
        $phpCsFile->fixer->addContent($varIndex, ' ' . $defaultValueType);
        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     * @param string $defaultValueType
     *
     * @return void
     */
    protected function addDocBlock(File $phpCsFile, int $stackPointer, string $defaultValueType): void
    {
        if ($defaultValueType === 'false') {
            $defaultValueType = 'bool';
        }

        $tokens = $phpCsFile->getTokens();

        $firstTokenOfLine = $this->getFirstTokenOfLine($tokens, $stackPointer);

        $prevContentIndex = $phpCsFile->findPrevious(T_WHITESPACE, $firstTokenOfLine - 1, null, true);
        if (!$prevContentIndex) {
            return;
        }

        if ($tokens[$prevContentIndex]['type'] === 'T_ATTRIBUTE_END') {
            $firstTokenOfLine = $this->getFirstTokenOfLine($tokens, $prevContentIndex);
        }

        $indentation = $this->getIndentationWhitespace($phpCsFile, $stackPointer);

        $phpCsFile->fixer->beginChangeset();
        $phpCsFile->fixer->addNewlineBefore($firstTokenOfLine);
        $phpCsFile->fixer->addContentBefore($firstTokenOfLine, $indentation . ' */');
        $phpCsFile->fixer->addNewlineBefore($firstTokenOfLine);
        $phpCsFile->fixer->addContentBefore($firstTokenOfLine, $indentation . ' * @var ' . $defaultValueType);
        $phpCsFile->fixer->addNewlineBefore($firstTokenOfLine);
        $phpCsFile->fixer->addContentBefore($firstTokenOfLine, $indentation . '/**');

        $phpCsFile->fixer->endChangeset();
    }
}
