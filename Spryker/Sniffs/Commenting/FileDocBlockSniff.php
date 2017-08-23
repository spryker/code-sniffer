<?php

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;

/**
 * Check if file has doc block comment and has the expected content
 */
class FileDocBlockSniff extends AbstractFileDocBlockSniff
{

    const FIRST_COMMENT_LINE_POSITION = 5;
    const SECOND_COMMENT_LINE_POSITION = 10;
    const EXPECTED_FILE_DOC_BLOCK_TOKEN_COUNT = 14;

    /**
     * This property can be filled within the ruleset configuration file
     *
     * @var array
     */
    public $ignorableBundles = [];

    /**
     * @inheritdoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        if (!$this->isSprykerNamespace($phpCsFile, $stackPointer) || $this->isIgnorableBundle($phpCsFile)) {
            $this->checkCustomFileDocBlock($phpCsFile, $stackPointer);
            return;
        }

        if (!$this->existsFileDocBlock($phpCsFile, $stackPointer)) {
            $this->addFixableMissingDocBlock($phpCsFile, $stackPointer);
            return;
        }

        if ($this->isOwnFileDocBlock($phpCsFile, $stackPointer)
            && ($this->hasNotExpectedLength($phpCsFile, $stackPointer) || $this->hasWrongContent($phpCsFile, $stackPointer))
        ) {
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
     *
     * @return bool
     */
    protected function isIgnorableBundle(File $phpCsFile)
    {
        return (in_array($this->getBundle($phpCsFile), $this->ignorableBundles));
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

        if ($firstLineComment !== sprintf(static::EXPECTED_COMMENT_FIRST_LINE_STRING, static::YEAR)
            || $secondLineComment !== static::EXPECTED_COMMENT_SECOND_LINE_STRING) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isOwnFileDocBlock(File $phpCsFile, $stackPointer)
    {
        $fileDockBlockTokens = $this->getFileDocBlockTokens($phpCsFile, $stackPointer);

        $firstLineComment = $fileDockBlockTokens[static::FIRST_COMMENT_LINE_POSITION]['content'];

        if (strpos($firstLineComment, 'modified by Spryker Systems GmbH') !== false) {
            return false;
        }

        return true;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return array
     */
    protected function getFileDocBlockTokens(File $phpCsFile, $stackPointer)
    {
        $fileDocBlockStartPosition = $phpCsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $stackPointer);
        $fileDocBlockEndPosition = $phpCsFile->findNext(T_DOC_COMMENT_CLOSE_TAG, $fileDocBlockStartPosition) + 1;

        $tokens = $phpCsFile->getTokens();

        return array_slice($tokens, $fileDocBlockStartPosition, $fileDocBlockEndPosition - $fileDocBlockStartPosition);
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
    protected function isCustomFileDocBlock(File $phpCsFile, $stackPointer, $license)
    {
        $fileDockBlockTokens = $this->getFileDocBlockTokens($phpCsFile, $stackPointer);

        $comment = '';
        foreach ($fileDockBlockTokens as $fileDockBlockToken) {
            $comment .= $fileDockBlockToken['content'];
        }

        if (trim($comment) === trim($license)) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     * @param string $license
     *
     * @return void
     */
    protected function addCustomFileDocBlock(File $phpCsFile, $stackPointer, $license)
    {
        $phpCsFile->fixer->beginChangeset();

        $this->clearFileDocBlock($phpCsFile, $stackPointer);

        $phpCsFile->fixer->addNewline($stackPointer);
        $phpCsFile->fixer->addContent($stackPointer, $license);
        $phpCsFile->fixer->addNewline($stackPointer);

        $phpCsFile->fixer->endChangeset();
    }

}
