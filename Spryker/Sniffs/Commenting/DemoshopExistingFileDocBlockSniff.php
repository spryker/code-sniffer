<?php

namespace Spryker\Sniffs\Commenting;

/**
 * Check if file has doc block comment and has the expected content
 */
class DemoshopExistingFileDocBlockSniff extends AbstractDemoshopFileDocBlockSniff
{

    const FIRST_COMMENT_LINE_POSITION = 5;
    const SECOND_COMMENT_LINE_POSITION = 10;
    const EXPECTED_FILE_DOC_BLOCK_TOKEN_COUNT = 14;

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    public function process(\PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        if (!$this->isPyzNamespace($phpCsFile, $stackPointer)) {
            return;
        }

        if ($this->existsFileDocBlock($phpCsFile, $stackPointer)
            && ($this->hasNotExpectedLength($phpCsFile, $stackPointer) || $this->hasWrongContent($phpCsFile, $stackPointer))
        ) {
            $this->addFixableExistingDocBlock($phpCsFile, $stackPointer);
        }
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasNotExpectedLength(\PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $fileDockBlockTokens = $this->getFileDocBlockTokens($phpCsFile, $stackPointer);

        return (count($fileDockBlockTokens) !== self::EXPECTED_FILE_DOC_BLOCK_TOKEN_COUNT);
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasWrongContent(\PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $fileDockBlockTokens = $this->getFileDocBlockTokens($phpCsFile, $stackPointer);

        $firstLineComment = $fileDockBlockTokens[self::FIRST_COMMENT_LINE_POSITION]['content'];
        $secondLineComment = $fileDockBlockTokens[self::SECOND_COMMENT_LINE_POSITION]['content'];

        if ($firstLineComment !== self::EXPECTED_COMMENT_FIRST_LINE_STRING
            || $secondLineComment !== self::EXPECTED_COMMENT_SECOND_LINE_STRING) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return array
     */
    protected function getFileDocBlockTokens(\PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $fileDocBlockStartPosition = $phpCsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $stackPointer);
        $fileDocBlockEndPosition = $phpCsFile->findNext(T_DOC_COMMENT_CLOSE_TAG, $fileDocBlockStartPosition) + 1;

        $tokens = $phpCsFile->getTokens();

        return array_slice($tokens, $fileDocBlockStartPosition, $fileDocBlockEndPosition - $fileDocBlockStartPosition);
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function addFixableExistingDocBlock(\PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $fix = $phpCsFile->addFixableError(basename($phpCsFile->getFilename()) . ' has the wrong file doc block', $stackPointer);
        if ($fix) {
            $this->addFileDocBlock($phpCsFile, 0);
        }
    }

}
