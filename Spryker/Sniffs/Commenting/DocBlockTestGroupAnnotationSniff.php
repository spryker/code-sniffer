<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use Exception;
use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Checks if doc blocks of Spryker test classes contain @group annotations.
 */
class DocBlockTestGroupAnnotationSniff extends AbstractSprykerSniff
{
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
        $filename = $phpCsFile->getFilename();

        preg_match('#/(src|tests)/(YvesUnit|YvesFunctional|SharedUnit|SharedFunctional|ZedUnit|ZedFunctional|Unit/Spryker|Functional/Spryker|Acceptance)/(.+)(Test|Cest).php$#', $filename, $matches);
        if (!$matches) {
            return;
        }

        $namespaceParts = $this->getNamespaceParts($phpCsFile, $stackPointer);
        if (!$namespaceParts) {
            return;
        }

        $groupAnnotationParts = $this->getGroupAnnotationParts($phpCsFile, $stackPointer);

        if ($namespaceParts === $groupAnnotationParts) {
            return;
        }

        $this->fixGroupAnnotation($phpCsFile, $stackPointer, $namespaceParts);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     * @param array $namespaceParts
     *
     * @return void
     */
    protected function fixGroupAnnotation(File $phpCsFile, int $stackPointer, array $namespaceParts): void
    {
        $fix = $phpCsFile->addFixableError('@group annotation missing or incomplete', $stackPointer, 'GroupAnnotation');

        if (!$fix) {
            return;
        }

        $docCommentEndPosition = $this->findRelatedDocBlock($phpCsFile, $stackPointer);

        if (!$docCommentEndPosition) {
            $this->addCommentWithGroupAnnotation($phpCsFile, $stackPointer, $namespaceParts);

            return;
        }

        $this->modifyExistingComment($phpCsFile, $docCommentEndPosition, $namespaceParts);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     * @param array $namespaceParts
     *
     * @return void
     */
    protected function addCommentWithGroupAnnotation(File $phpCsFile, int $stackPointer, array $namespaceParts): void
    {
        $tokens = $phpCsFile->getTokens();

        $startPosition = $stackPointer;
        while ($tokens[$startPosition - 1]['line'] === $tokens[$stackPointer]['line']) {
            $startPosition--;
        }

        $startPosition--;

        $phpCsFile->fixer->beginChangeset();

        $phpCsFile->fixer->addContent($startPosition, '/**');
        $phpCsFile->fixer->addNewline($startPosition);

        foreach ($namespaceParts as $namespacePart) {
            $phpCsFile->fixer->addContent($startPosition, ' * @group ' . $namespacePart);
            $phpCsFile->fixer->addNewline($startPosition);
        }

        $phpCsFile->fixer->addContent($startPosition, ' */');
        $phpCsFile->fixer->addNewline($startPosition);

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docCommentEndPosition
     * @param array $namespaceParts
     *
     * @return void
     */
    protected function modifyExistingComment(File $phpCsFile, int $docCommentEndPosition, array $namespaceParts): void
    {
        $tokens = $phpCsFile->getTokens();

        $docCommentStartPosition = $tokens[$docCommentEndPosition]['comment_opener'];

        $firstGroupTagPosition = $this->findGroupTagPosition($phpCsFile, $docCommentStartPosition, $docCommentStartPosition);
        $startPosition = $firstGroupTagPosition;

        if (!$startPosition) {
            $startPosition = $this->getLastLineOfDocBlock($phpCsFile, $docCommentStartPosition);
        }

        $phpCsFile->fixer->beginChangeset();

        if ($firstGroupTagPosition) {
            $lastGroupTagPosition = $this->getGroupTagPositionEnd($phpCsFile, $docCommentStartPosition, $firstGroupTagPosition);
            for ($i = $firstGroupTagPosition; $i <= $lastGroupTagPosition; $i++) {
                $phpCsFile->fixer->replaceToken($i, '');
            }
        }

        foreach ($namespaceParts as $namespacePart) {
            $phpCsFile->fixer->addContent($startPosition, ' * @group ' . $namespacePart);
            $phpCsFile->fixer->addNewline($startPosition);
        }

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return string[]
     */
    protected function getNamespaceParts(File $phpCsFile, int $stackPointer): array
    {
        $namespace = $this->getNamespaceStatement($phpCsFile);
        if (!$namespace) {
            return [];
        }

        $parts = explode('\\', $namespace['namespace']);

        $name = $this->getClassOrInterfaceName($phpCsFile, $stackPointer);
        $parts[] = $name;

        return $parts;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return string
     */
    protected function getClassOrInterfaceName(File $phpCsFile, int $stackPointer): string
    {
        $classOrInterfacePosition = $phpCsFile->findPrevious([T_CLASS, T_INTERFACE], $stackPointer);
        $classOrInterfaceNamePosition = $phpCsFile->findNext(T_STRING, $classOrInterfacePosition);

        return $phpCsFile->getTokens()[$classOrInterfaceNamePosition]['content'];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return string[]
     */
    protected function getGroupAnnotationParts(File $phpCsFile, int $stackPointer): array
    {
        $tokens = $phpCsFile->getTokens();

        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);
        if (!$docBlockEndIndex) {
            return [];
        }

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        $parts = [];
        for ($i = $docBlockStartIndex; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['content'] !== '@group') {
                continue;
            }

            $i = $i + 2;
            $parts[] = $tokens[$i]['content'];
        }

        return $parts;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docCommentStartPosition
     * @param int $firstDocCommentTagPosition
     *
     * @return int|null
     */
    protected function findGroupTagPosition(
        File $phpCsFile,
        int $docCommentStartPosition,
        int $firstDocCommentTagPosition
    ): ?int {
        $tokens = $phpCsFile->getTokens();
        $docEndIndex = $tokens[$docCommentStartPosition]['comment_closer'];

        for ($i = $firstDocCommentTagPosition; $i < $docEndIndex; $i++) {
            if ($tokens[$i]['content'] !== '@group') {
                continue;
            }

            while ($tokens[$i - 1]['line'] === $tokens[$i]['line']) {
                $i--;
            }

            return $i;
        }

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docCommentStartPosition
     *
     * @return int
     */
    protected function getLastLineOfDocBlock(File $phpCsFile, int $docCommentStartPosition): int
    {
        $tokens = $phpCsFile->getTokens();
        $i = $tokens[$docCommentStartPosition]['comment_closer'];

        while ($tokens[$i - 1]['line'] === $tokens[$i]['line']) {
            $i--;
        }

        return $i;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docCommentStartPosition
     * @param int $firstGroupTagPosition
     *
     * @throws \Exception
     *
     * @return int
     */
    protected function getGroupTagPositionEnd(
        File $phpCsFile,
        int $docCommentStartPosition,
        int $firstGroupTagPosition
    ): int {
        $tokens = $phpCsFile->getTokens();

        $docCommentCloserPosition = $tokens[$docCommentStartPosition]['comment_closer'];

        for ($i = $docCommentCloserPosition; $i > $firstGroupTagPosition; $i--) {
            if ($tokens[$i]['content'] !== '@group') {
                continue;
            }

            while ($tokens[$i + 1]['line'] === $tokens[$i]['line']) {
                $i++;
            }

            return $i;
        }

        throw new Exception('Not possible');
    }
}
