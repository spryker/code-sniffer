<?php

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer_File;

/**
 * Checks if file doc block exists
 */
class MissingFileDocBlockSniff extends AbstractFileDocBlockSniff
{

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        if (!$this->isSprykerNamespace($phpCsFile, $stackPointer)) {
            return;
        }

        if (!$this->existsFileDocBlock($phpCsFile, $stackPointer)) {
            $this->addFixableMissingDocblock($phpCsFile, $stackPointer);
        }
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function addFixableMissingDocBlock(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $fix = $phpCsFile->addFixableError(basename($phpCsFile->getFilename()) . ' has no File Doc Block.', $stackPointer);
        if ($fix) {
            $this->addFileDocBlock($phpCsFile, 0);
        }
    }

}
