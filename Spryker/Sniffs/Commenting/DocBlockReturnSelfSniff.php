<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Doc blocks should type-hint returning itself as $this for fluent interface to work.
 */
class DocBlockReturnSelfSniff extends AbstractSprykerSniff
{
    /**
     * @inheritdoc
     */
    public function register()
    {
        return [
            T_FUNCTION,
        ];
    }

    /**
     * @inheritdoc
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
            if (!in_array($tokens[$i]['content'], ['@return'])) {
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
            $this->assertCorrectDocBlockParts($phpCsFile, $classNameIndex, $parts, $appendix);
            $this->fixClassToThis($phpCsFile, $stackPointer, $classNameIndex, $parts, $appendix);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $classNameIndex
     * @param array $parts
     * @param string $appendix
     *
     * @return void
     */
    protected function assertCorrectDocBlockParts(File $phpCsFile, $classNameIndex, array $parts, $appendix)
    {
        $result = [];
        foreach ($parts as $key => $part) {
            if ($part !== 'self') {
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
    protected function findRelatedDocBlock(File $phpCsFile, $stackPointer)
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
    protected function isStaticMethod(File $phpCsFile, $stackPointer)
    {
        $tokens = $phpCsFile->getTokens();

        if (!in_array($tokens[$stackPointer]['code'], [T_FUNCTION])) {
            return false;
        }

        $methodProperties = $phpCsFile->getMethodProperties($stackPointer);

        return $methodProperties['is_static'];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     * @param int $classNameIndex
     * @param array $parts
     * @param string $appendix
     *
     * @return void
     */
    protected function fixClassToThis(File $phpCsFile, $stackPointer, $classNameIndex, $parts, $appendix)
    {
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

        $isFluentInterfaceMethod = $this->isFluentInterfaceMethod($phpCsFile, $stackPointer);
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
     * @return bool
     */
    protected function isFluentInterfaceMethod(File $phpCsFile, $stackPointer)
    {
        $tokens = $phpCsFile->getTokens();

        // We skip for interface methods
        if (empty($tokens[$stackPointer]['scope_opener']) || empty($tokens[$stackPointer]['scope_closer'])) {
            return false;
        }

        $scopeOpener = $tokens[$stackPointer]['scope_opener'];
        $scopeCloser = $tokens[$stackPointer]['scope_closer'];

        for ($i = $scopeOpener; $i < $scopeCloser; $i++) {
            if ($tokens[$i]['code'] !== T_RETURN) {
                continue;
            }

            $contentIndex = $phpCsFile->findNext(Tokens::$emptyTokens, $i + 1, $scopeCloser, true);
            if (!$contentIndex) {
                return false;
            }

            if ($tokens[$contentIndex]['content'] === '$this') {
                return true;
            }
        }

        return false;
    }
}
