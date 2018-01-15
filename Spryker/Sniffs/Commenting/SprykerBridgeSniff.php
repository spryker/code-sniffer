<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Checks if Spryker Bridge classes have a type-hinting-less constructor.
 */
class SprykerBridgeSniff implements Sniff
{
    /**
     * @return array
     */
    public function register()
    {
        return [
            T_CLASS,
        ];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        if (!$this->isSprykerBridge($phpCsFile, $stackPointer)) {
            return;
        }

        $this->checkBridge($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function checkBridge(File $phpCsFile, $stackPointer)
    {
        $index = $stackPointer;
        while ($index = $phpCsFile->findNext(T_FUNCTION, $index + 1)) {
            $methodName = $phpCsFile->getDeclarationName($index);
            if ($methodName !== '__construct') {
                continue;
            };

            $this->assertValidConstructor($phpCsFile, $index);
            $this->assertValidDocBlock($phpCsFile, $index);
            break;
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $index
     *
     * @return void
     */
    protected function assertValidConstructor(File $phpCsFile, $index)
    {
        $parameters = $phpCsFile->getMethodParameters($index);
        foreach ($parameters as $parameter) {
            if (empty($parameter['type_hint'])) {
                break;
            }

            $fix = $phpCsFile->addFixableError('Bridge constructors must not have typehints.', $parameter['token'], 'InvalidTypehint');
            if (!$fix) {
                break;
            }

            $variableIndex = $parameter['token'];
            $typehintIndex = $phpCsFile->findPrevious(T_STRING, $variableIndex - 1);

            $phpCsFile->fixer->beginChangeset();
            for ($i = $typehintIndex; $i < $variableIndex; $i++) {
                $phpCsFile->fixer->replaceToken($i, '');
            }
            $phpCsFile->fixer->endChangeset();
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isSprykerBridge(File $phpCsFile, $stackPointer)
    {
        if (!$this->hasNamespace($phpCsFile, $stackPointer)) {
            return false;
        }

        $namespace = $this->getNamespace($phpCsFile, $stackPointer);
        if (!preg_match('/^Spryker\\\\/', $namespace)) {
            return false;
        }

        $name = $this->findClassOrInterfaceName($phpCsFile, $stackPointer);
        if (!$name || substr($name, -6) !== 'Bridge') {
            return false;
        }

        return true;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasNamespace(File $phpCsFile, $stackPointer)
    {
        $namespacePosition = $phpCsFile->findPrevious(T_NAMESPACE, $stackPointer);
        if (!$namespacePosition) {
            return false;
        }

        return true;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return string
     */
    protected function getNamespace(File $phpCsFile, $stackPointer)
    {
        $namespacePosition = $phpCsFile->findPrevious(T_NAMESPACE, $stackPointer);
        $endOfNamespacePosition = $phpCsFile->findEndOfStatement($namespacePosition);

        $tokens = $phpCsFile->getTokens();
        $namespaceTokens = array_splice($tokens, $namespacePosition + 2, $endOfNamespacePosition - $namespacePosition - 2);

        $namespace = '';
        foreach ($namespaceTokens as $token) {
            $namespace .= $token['content'];
        }

        return $namespace;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return string
     */
    protected function findClassOrInterfaceName(File $phpCsFile, $stackPointer)
    {
        $classOrInterfaceNamePosition = $phpCsFile->findNext(T_STRING, $stackPointer);

        return $phpCsFile->getTokens()[$classOrInterfaceNamePosition]['content'];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $methodIndex
     *
     * @return void
     */
    protected function assertValidDocBlock(File $phpCsFile, $methodIndex)
    {
        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $methodIndex);
        if (!$docBlockEndIndex) {
            // Let another sniff take care of this
            return;
        }

        $tokens = $phpCsFile->getTokens();

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if (!in_array($tokens[$i]['content'], ['@param', '@return'], true)) {
                continue;
            }

            $classNameIndex = $i + 2;

            if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
                continue;
            }

            $content = $tokens[$classNameIndex]['content'];

            $spacePos = strpos($content, ' ');
            if ($spacePos) {
                $content = substr($content, 0, $spacePos);
            }
            if (substr($content, 0, 8) !== '\Spryker') {
                continue;
            }
            if (substr($content, -9) === 'Interface') {
                continue;
            }
            $pieces = explode('\\', $content);
            $lastPiece = array_pop($pieces);

            if (!$this->isRelevant($lastPiece)) {
                continue;
            }

            $error = 'Bridges should be annotated with interfaces only.';
            $phpCsFile->addError($error, $classNameIndex, 'WrongAnnotation');
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
     * @param string $content
     *
     * @return bool
     */
    protected function isRelevant($content)
    {
        $whitelist = [
            'Facade',
            'Service',
            'QueryContainer',
            'Client',
        ];

        foreach ($whitelist as $classType) {
            $length = strlen($classType);
            if (substr($content, -$length) === $classType) {
                return true;
            }
        }

        return false;
    }
}
