<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Checks if Spryker Constants classes have a note about ENV config.
 */
class SprykerConstantsSniff extends AbstractSprykerSniff
{
    protected const EXPLANATION_CONSTANTS_INTERFACE = 'Declares global environment configuration keys. Do not use it for other class constants.';

    /**
     * We must support class for now, as well - for BC.
     *
     * @return array
     */
    public function register(): array
    {
        return [
            T_CLASS, T_INTERFACE,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        if (!$this->isSprykerConstantsInterface($phpCsFile, $stackPointer)) {
            return;
        }

        $this->checkConstantsInterface($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function checkConstantsInterface(File $phpCsFile, int $stackPointer): void
    {
        $docBlockEndIndex = $this->findDocBlock($phpCsFile, $stackPointer);

        if (!$docBlockEndIndex) {
            $this->addNewDocBlock($phpCsFile, $stackPointer);

            return;
        }

        $this->checkExistingDocBlock($phpCsFile, $docBlockEndIndex);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function addNewDocBlock(File $phpCsFile, int $stackPointer): void
    {
        $fix = $phpCsFile->addFixableError('Missing Constants interface doc block.', $stackPointer, 'DocBlockMissing');
        if (!$fix) {
            return;
        }

        $tokens = $phpCsFile->getTokens();
        $docBlockStartPosition = $stackPointer;
        while ($tokens[$docBlockStartPosition - 1]['line'] === $docBlockStartPosition) {
            $docBlockStartPosition--;
        }

        $docBlockStartPosition--;

        $phpCsFile->fixer->beginChangeset();
        $this->insertDocBlock($phpCsFile, $docBlockStartPosition);
        $phpCsFile->fixer->addNewline($docBlockStartPosition);
        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockEndPosition
     *
     * @return void
     */
    protected function checkExistingDocBlock(File $phpCsFile, int $docBlockEndPosition): void
    {
        if ($this->hasCorrectContent($phpCsFile, $docBlockEndPosition)) {
            return;
        }

        $fix = $phpCsFile->addFixableError('Constants interface doc block outdated.', $docBlockEndPosition, 'DocBlockOutdated');
        if (!$fix) {
            return;
        }

        $tokens = $phpCsFile->getTokens();
        $docBlockStartPosition = $tokens[$docBlockEndPosition]['comment_opener'];

        $phpCsFile->fixer->beginChangeset();
        for ($i = $docBlockStartPosition; $i <= $docBlockEndPosition; $i++) {
            $phpCsFile->fixer->replaceToken($i, '');
        }

        $docBlockStartPosition--;
        $this->insertDocBlock($phpCsFile, $docBlockStartPosition);
        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isSprykerConstantsInterface(File $phpCsFile, int $stackPointer): bool
    {
        if (!$this->hasNamespace($phpCsFile, $stackPointer)) {
            return false;
        }

        $namespace = $this->getInterfaceNamespace($phpCsFile, $stackPointer);
        if (!preg_match('/^Spryker.*\\\\Shared\\\\/', $namespace)) {
            return false;
        }

        $name = $this->findClassOrInterfaceName($phpCsFile, $stackPointer);
        if (!$name || substr($name, -9) !== 'Constants') {
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
    protected function hasNamespace(File $phpCsFile, int $stackPointer): bool
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
    protected function getInterfaceNamespace(File $phpCsFile, int $stackPointer): string
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
    protected function findClassOrInterfaceName(File $phpCsFile, int $stackPointer): string
    {
        $classOrInterfaceNamePosition = $phpCsFile->findNext(T_STRING, $stackPointer);

        return $phpCsFile->getTokens()[$classOrInterfaceNamePosition]['content'];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return int|null Stackpointer value of docblock end tag, or null if cannot be found
     */
    protected function findDocBlock(File $phpCsFile, int $stackPointer): ?int
    {
        $tokens = $phpCsFile->getTokens();

        $index = $phpCsFile->findPrevious(T_WHITESPACE, $stackPointer - 1, null, true);

        if ($tokens[$index]['type'] === 'T_DOC_COMMENT_CLOSE_TAG') {
            return $index;
        }

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockEndPosition
     *
     * @return bool
     */
    protected function hasCorrectContent(File $phpCsFile, int $docBlockEndPosition): bool
    {
        $tokens = $phpCsFile->getTokens();

        $docBlockStartPosition = $tokens[$docBlockEndPosition]['comment_opener'];

        $content = '';
        for ($i = $docBlockStartPosition + 1; $i < $docBlockEndPosition; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_STRING') {
                continue;
            }
            $content .= $tokens[$i]['content'];
        }

        if ($content === static::EXPLANATION_CONSTANTS_INTERFACE) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockStartPosition
     *
     * @return void
     */
    protected function insertDocBlock(File $phpCsFile, int $docBlockStartPosition): void
    {
        $phpCsFile->fixer->addContent($docBlockStartPosition, '/**');
        $phpCsFile->fixer->addNewline($docBlockStartPosition);
        $phpCsFile->fixer->addContent($docBlockStartPosition, ' * ' . static::EXPLANATION_CONSTANTS_INTERFACE);
        $phpCsFile->fixer->addNewline($docBlockStartPosition);
        $phpCsFile->fixer->addContent($docBlockStartPosition, ' */');
    }
}
