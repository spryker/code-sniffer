<?php

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;

/**
 * Checks if Spryker Demoshop's files have doc block comment and have the expected content.
 * This sniff is skipped for customer's projects.
 */
class DemoshopFileDocBlockSniff extends AbstractDemoshopFileDocBlockSniff
{

    const FIRST_COMMENT_LINE_POSITION = 5;
    const SECOND_COMMENT_LINE_POSITION = 10;
    const EXPECTED_FILE_DOC_BLOCK_TOKEN_COUNT = 14;

    /**
     * @inheritdoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        if (!$this->isPyzNamespace($phpCsFile, $stackPointer) || !$this->isDemoshop($phpCsFile)) {
            $this->checkCustomFileDocBlock($phpCsFile, $stackPointer);
            return;
        }

        if (!$this->existsFileDocBlock($phpCsFile, $stackPointer)) {
            $this->addFixableMissingDocblock($phpCsFile, $stackPointer);
            return;
        }

        if ($this->hasNotExpectedLength($phpCsFile, $stackPointer) || $this->hasWrongContent($phpCsFile, $stackPointer)) {
            $this->addFixableExistingDocBlock($phpCsFile, $stackPointer);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function addFixableMissingDocBlock(File $phpCsFile, $stackPointer)
    {
        $fix = $phpCsFile->addFixableError(basename($phpCsFile->getFilename()) . ' has no File Doc Block.', $stackPointer, 'FileDocBlockMissing');
        if ($fix) {
            $this->addFileDocBlock($phpCsFile, 0);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function checkCustomFileDocBlock(File $phpCsFile, $stackPointer)
    {
        $file = getcwd() . DIRECTORY_SEPARATOR . '.license';
        $license = $this->getLicense($file);
        if (!$license) {
            return;
        }

        if (!$this->existsFileDocBlock($phpCsFile, $stackPointer)) {
            $fix = $phpCsFile->addFixableError(basename($phpCsFile->getFilename()) . ' has no File Doc Block.', $stackPointer, 'CustomFileDocBlockMissing');
            if ($fix) {
                $this->addFileDocBlock($phpCsFile, 0);
            }
            return;
        }

        if ($this->isCustomFileDocBlock($phpCsFile, $stackPointer, $license)) {
            return;
        }

        $fix = $phpCsFile->addFixableError(basename($phpCsFile->getFilename()) . ' has the wrong file doc block', $stackPointer, 'CustomFileDocBlockWrong');
        if ($fix) {
            $this->addCustomFileDocBlock($phpCsFile, 0, $license);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasNotExpectedLength(File $phpCsFile, $stackPointer)
    {
        $fileDockBlockTokens = $this->getFileDocBlockTokens($phpCsFile, $stackPointer);

        return (count($fileDockBlockTokens) !== static::EXPECTED_FILE_DOC_BLOCK_TOKEN_COUNT);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasWrongContent(File $phpCsFile, $stackPointer)
    {
        $fileDockBlockTokens = $this->getFileDocBlockTokens($phpCsFile, $stackPointer);

        $firstLineComment = $fileDockBlockTokens[static::FIRST_COMMENT_LINE_POSITION]['content'];
        $secondLineComment = $fileDockBlockTokens[static::SECOND_COMMENT_LINE_POSITION]['content'];

        if ($firstLineComment !== static::EXPECTED_COMMENT_FIRST_LINE_STRING
            || $secondLineComment !== static::EXPECTED_COMMENT_SECOND_LINE_STRING) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function addFixableExistingDocBlock(File $phpCsFile, $stackPointer)
    {
        $fix = $phpCsFile->addFixableError(basename($phpCsFile->getFilename()) . ' has the wrong file doc block', $stackPointer, 'FileDocBlockWrong');
        if ($fix) {
            $this->addFileDocBlock($phpCsFile, 0);
        }
    }

}
