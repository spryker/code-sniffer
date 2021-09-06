<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Tools\Traits\CommentingTrait;

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
    public function process(File $phpCsFile, $stackPointer)
    {
        $tokens = $phpCsFile->getTokens();

        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);

        if (!$docBlockEndIndex) {
            $defaultValueType = $this->findDefaultValueType($phpCsFile, $stackPointer);
            if ($defaultValueType === null) {
                $phpCsFile->addError('Doc Block for const missing', $stackPointer, 'VarDocBlockMissing');

                return;
            }

            $phpCsFile->addFixableError('Doc Block for const missing', $stackPointer, 'VarDocBlockMissing');
            $this->addDocBlock($phpCsFile, $stackPointer, $defaultValueType);

            return;
        }

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

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

        $appendix = '';
        $spaceIndex = strpos($content, ' ');
        if ($spaceIndex) {
            $appendix = substr($content, $spaceIndex);
            $content = substr($content, 0, $spaceIndex);
        }

        if (empty($content)) {
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

        $parts = explode('|', $content);
        if (in_array($defaultValueType, $parts, true)) {
            return;
        }
        if ($defaultValueType === 'array' && $this->containsTypeArray($parts)) {
            return;
        }
        if ($defaultValueType === 'false' && in_array('bool', $parts, true)) {
            return;
        }

        if ($defaultValueType === 'false') {
            $defaultValueType = 'bool';
        }

        if (count($parts) > 1 || $defaultValueType === 'null') {
            $fix = $phpCsFile->addFixableError('Doc Block type for property annotation @var incorrect, type `' . $defaultValueType . '` missing', $stackPointer, 'VarTypeMissing');
            if ($fix) {
                $phpCsFile->fixer->beginChangeset();
                $phpCsFile->fixer->replaceToken($typeIndex, implode('|', $parts) . '|' . $defaultValueType . $appendix);
                $phpCsFile->fixer->endChangeset();
            }

            return;
        }

        $fix = $phpCsFile->addFixableError('Doc Block type `' . $content . '` for property annotation @var incorrect, type `' . $defaultValueType . '` expected', $stackPointer, 'VarTypeIncorrect');
        if ($fix) {
            $phpCsFile->fixer->beginChangeset();
            $phpCsFile->fixer->replaceToken($typeIndex, $defaultValueType . $appendix);
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
     * @param array $token
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
        // Let's skip for now for non-trivial cases
        if ($defaultValueType === null) {
            return;
        }

        $error = 'Doc Block annotation @var for const missing';
        if ($defaultValueType === null) {
            $phpCsFile->addError($error, $docBlockEndIndex, 'DocBlockMissing');

            return;
        }

        if ($defaultValueType === 'false') {
            $defaultValueType = 'bool';
        }

        $error .= ', type `' . $defaultValueType . '` detected';
        $fix = $phpCsFile->addFixableError($error, $docBlockEndIndex, 'WrongType');
        if (!$fix) {
            return;
        }

        $index = $phpCsFile->findPrevious(Tokens::$emptyTokens, $docBlockEndIndex - 1, $docBlockStartIndex, true);
        if (!$index) {
            $index = $docBlockStartIndex;
        }

        $phpCsFile->fixer->beginChangeset();
        $phpCsFile->fixer->addNewline($index);
        $phpCsFile->fixer->addContent($index, "\t" . ' * @var ' . $defaultValueType);
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
