<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Doc blocks should type-hint returning itself as $this for fluent interface to work.
 * Chainable methods declared as such must not have any other return type in code.
 */
class DocBlockReturnSelfSniff extends AbstractSprykerSniff
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
        $tokens = $phpCsFile->getTokens();

        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);

        if (!$docBlockEndIndex) {
            return;
        }

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if ($tokens[$i]['content'] !== '@return') {
                continue;
            }

            $classNameIndex = $i + 2;

            if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
                continue;
            }

            $content = $tokens[$classNameIndex]['content'];

            $appendix = '';
            $spaceIndex = strpos($content, ' ');
            if ($spaceIndex) {
                $appendix = substr($content, $spaceIndex);
                $content = substr($content, 0, $spaceIndex);
            }

            if (empty($content)) {
                continue;
            }

            if ($this->isStaticMethod($phpCsFile, $stackPointer)) {
                continue;
            }

            $parts = explode('|', $content);
            $returnTypes = $this->getReturnTypes($phpCsFile, $stackPointer);

            $this->assertCorrectDocBlockParts($phpCsFile, $classNameIndex, $parts, $returnTypes, $appendix);

            $this->assertChainableReturnType($phpCsFile, $stackPointer, $parts, $returnTypes);
            $this->fixClassToThis($phpCsFile, $classNameIndex, $parts, $appendix, $returnTypes);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $classNameIndex
     * @param string[] $parts
     * @param string[] $returnTypes
     * @param string $appendix
     *
     * @return void
     */
    protected function assertCorrectDocBlockParts(
        File $phpCsFile,
        int $classNameIndex,
        array $parts,
        array $returnTypes,
        string $appendix
    ): void {
        $result = [];
        foreach ($parts as $key => $part) {
            if ($part !== 'self') {
                continue;
            }
            if ($returnTypes !== ['$this']) {
                continue;
            }

            $parts[$key] = '$this';
            $result[$part] = '$this';
        }

        if (!$result) {
            return;
        }

        $message = [];
        foreach ($result as $part => $useStatement) {
            $message[] = $part . ' => ' . $useStatement;
        }

        $fix = $phpCsFile->addFixableError(implode(', ', $message), $classNameIndex, 'SelfVsThis');
        if ($fix) {
            $newContent = implode('|', $parts);
            $phpCsFile->fixer->replaceToken($classNameIndex, $newContent . $appendix);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return int|null Stackpointer value of docblock end tag, or null if cannot be found
     */
    protected function findRelatedDocBlock(File $phpCsFile, int $stackPointer): ?int
    {
        $tokens = $phpCsFile->getTokens();

        $line = $tokens[$stackPointer]['line'];
        $beginningOfLine = $stackPointer;
        while (!empty($tokens[$beginningOfLine - 1]) && $tokens[$beginningOfLine - 1]['line'] === $line) {
            $beginningOfLine--;
        }

        if (!empty($tokens[$beginningOfLine - 2]) && $tokens[$beginningOfLine - 2]['type'] === 'T_DOC_COMMENT_CLOSE_TAG') {
            return $beginningOfLine - 2;
        }

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isStaticMethod(File $phpCsFile, int $stackPointer): bool
    {
        $tokens = $phpCsFile->getTokens();

        if (!in_array($tokens[$stackPointer]['code'], [T_FUNCTION], true)) {
            return false;
        }

        $methodProperties = $phpCsFile->getMethodProperties($stackPointer);

        return $methodProperties['is_static'];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $classNameIndex
     * @param string[] $parts
     * @param string $appendix
     * @param string[] $returnTypes
     *
     * @return void
     */
    protected function fixClassToThis(
        File $phpCsFile,
        int $classNameIndex,
        array $parts,
        string $appendix,
        array $returnTypes
    ): void {
        $ownClassName = '\\' . $this->getClassName($phpCsFile);

        $result = [];
        foreach ($parts as $key => $part) {
            if ($part !== $ownClassName) {
                continue;
            }

            $parts[$key] = '$this';
            $result[$part] = '$this';
        }

        if (!$result) {
            return;
        }

        $isFluentInterfaceMethod = $returnTypes === ['$this'];
        if (!$isFluentInterfaceMethod) {
            return;
        }

        $message = [];
        foreach ($result as $part => $useStatement) {
            $message[] = $part . ' => ' . $useStatement;
        }

        $fix = $phpCsFile->addFixableError(implode(', ', $message), $classNameIndex, 'ClassVsThis');
        if ($fix) {
            $newContent = implode('|', $parts);
            $phpCsFile->fixer->replaceToken($classNameIndex, $newContent . $appendix);
        }
    }

    /**
     * We want to skip for static or other non chainable use cases.
     *
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return string[]
     */
    protected function getReturnTypes(File $phpCsFile, int $stackPointer): array
    {
        $tokens = $phpCsFile->getTokens();

        // We skip for interface methods
        if (empty($tokens[$stackPointer]['scope_opener']) || empty($tokens[$stackPointer]['scope_closer'])) {
            return [];
        }

        $scopeOpener = $tokens[$stackPointer]['scope_opener'];
        $scopeCloser = $tokens[$stackPointer]['scope_closer'];

        $returnTypes = [];
        for ($i = $scopeOpener; $i < $scopeCloser; $i++) {
            if ($tokens[$i]['code'] !== T_RETURN) {
                continue;
            }

            if (in_array(T_CLOSURE, $tokens[$i]['conditions'], true)) {
                continue;
            }

            $contentIndex = $phpCsFile->findNext(Tokens::$emptyTokens, $i + 1, $scopeCloser, true);
            if (!$contentIndex) {
                continue;
            }

            if ($tokens[$contentIndex]['code'] === T_PARENT) {
                $parentMethodName = $tokens[$contentIndex + 2]['content'];

                if ($parentMethodName === FunctionHelper::getName($phpCsFile, $stackPointer)) {
                    continue;
                }
            }

            $content = $tokens[$contentIndex]['content'];

            $nextIndex = $phpCsFile->findNext(Tokens::$emptyTokens, $contentIndex + 1, $scopeCloser, true);
            if (!$nextIndex) {
                continue;
            }
            if ($tokens[$nextIndex]['code'] !== T_SEMICOLON) {
                $k = $nextIndex;
                while ($k < $scopeCloser && $tokens[$k]['code'] !== T_SEMICOLON) {
                    $content .= $tokens[$k]['content'];
                    $k++;
                }
            }

            $returnTypes[] = $content;
        }

        return array_unique($returnTypes);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     * @param string[] $parts
     * @param string[] $returnTypes
     *
     * @return void
     */
    protected function assertChainableReturnType(
        File $phpCsFile,
        int $stackPointer,
        array $parts,
        array $returnTypes
    ): void {
        if ($returnTypes && $parts === ['$this'] && $returnTypes !== ['$this']) {
            $phpCsFile->addError('Chainable method (@return $this) cannot have multiple return types in code.', $stackPointer, 'InvalidChainable');
        }
    }
}
