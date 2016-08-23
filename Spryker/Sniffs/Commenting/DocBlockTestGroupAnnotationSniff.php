<?php

namespace Spryker\Sniffs\Commenting;

use Exception;
use PHP_CodeSniffer_File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Checks if doc blocks of Spryker test classes contain @group annotations.
 */
class DocBlockTestGroupAnnotationSniff extends AbstractSprykerSniff
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
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $filename = $phpCsFile->getFilename();

        preg_match('#/(src|tests)/(YvesUnit|YvesFunctional|Unit/Spryker|Functional/Spryker|Acceptance)/(.+)(Test|Cest).php$#', $filename, $matches);
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
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     * @param array $namespaceParts
     *
     * @return void
     */
    protected function fixGroupAnnotation(PHP_CodeSniffer_File $phpCsFile, $stackPointer, array $namespaceParts)
    {
        $fix = $phpCsFile->addFixableError('@group annotation missing or incomplete', $stackPointer);

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
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     * @param array $namespaceParts
     *
     * @return void
     */
    protected function addCommentWithGroupAnnotation(PHP_CodeSniffer_File $phpCsFile, $stackPointer, array $namespaceParts)
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
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $docCommentEndPosition
     * @param array $namespaceParts
     *
     * @return void
     */
    protected function modifyExistingComment(PHP_CodeSniffer_File $phpCsFile, $docCommentEndPosition, array $namespaceParts)
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
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return array
     */
    protected function getNamespaceParts(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
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
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return string
     */
    protected function getClassOrInterfaceName(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $classOrInterfacePosition = $phpCsFile->findPrevious([T_CLASS, T_INTERFACE], $stackPointer);
        $classOrInterfaceNamePosition = $phpCsFile->findNext(T_STRING, $classOrInterfacePosition);

        return $phpCsFile->getTokens()[$classOrInterfaceNamePosition]['content'];
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return array
     */
    protected function getGroupAnnotationParts(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
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
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $docCommentStartPosition
     * @param int $firstDocCommentTagPosition
     *
     * @return int|null
     */
    protected function findGroupTagPosition(PHP_CodeSniffer_File $phpCsFile, $docCommentStartPosition, $firstDocCommentTagPosition)
    {
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
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $docCommentStartPosition
     *
     * @return int
     */
    protected function getLastLineOfDocBlock(PHP_CodeSniffer_File $phpCsFile, $docCommentStartPosition)
    {
        $tokens = $phpCsFile->getTokens();
        $i = $tokens[$docCommentStartPosition]['comment_closer'];

        while ($tokens[$i - 1]['line'] === $tokens[$i]['line']) {
            $i--;
        }

        return $i;
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $docCommentStartPosition
     * @param int $firstGroupTagPosition
     *
     * @throws \Exception
     *
     * @return int
     */
    protected function getGroupTagPositionEnd(PHP_CodeSniffer_File $phpCsFile, $docCommentStartPosition, $firstGroupTagPosition)
    {
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
