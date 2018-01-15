<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

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

        $customLicense = $this->findCustomLicense($phpCsFile);

        if (!$this->existsFileDocBlock($phpCsFile, $stackPointer)) {
            if ($customLicense) {
                $fix = $phpCsFile->addFixableError(basename($phpCsFile->getFilename()) . ' has no File Doc Block.', $stackPointer, 'CustomFileDocBlockMissing');
                if ($fix) {
                    $this->addCustomFileDocBlock($phpCsFile, 0, $customLicense);
                }
            } else {
                $this->addFixableMissingDocBlock($phpCsFile, $stackPointer);
            }
            return;
        }

        if (!$this->isOwnFileDocBlock($phpCsFile, $stackPointer)) {
            return;
        }

        if ($this->isCustomFileDocBlock($phpCsFile, $stackPointer, $customLicense)) {
            return;
        }

        if ($customLicense) {
            $fix = $phpCsFile->addFixableError(basename($phpCsFile->getFilename()) . ' has the wrong file doc block', $stackPointer, 'FileDocBlockWrong');
            if ($fix) {
                $this->addCustomFileDocBlock($phpCsFile, 0, $customLicense);
            }
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
     *
     * @return bool
     */
    protected function isIgnorableBundle(File $phpCsFile)
    {
        return (in_array($this->getModule($phpCsFile), $this->ignorableBundles));
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
     *
     * @return null|string
     */
    protected function findCustomLicense(File $phpCsFile)
    {
        $path = str_replace(getcwd(), '', $phpCsFile->getFilename());

        if (strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'spryker' . DIRECTORY_SEPARATOR . 'spryker' . DIRECTORY_SEPARATOR) === 0) {
            $pathArray = explode(DIRECTORY_SEPARATOR, substr($path, 8));
            array_shift($pathArray);
            array_shift($pathArray);
            array_shift($pathArray);

            $path = getcwd() . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR
                . 'spryker' . DIRECTORY_SEPARATOR . 'spryker' . DIRECTORY_SEPARATOR
                . 'Bundles' . DIRECTORY_SEPARATOR . array_shift($pathArray) . DIRECTORY_SEPARATOR;

            return $this->getLicense($path) ?: null;
        }

        return null;
    }
}
