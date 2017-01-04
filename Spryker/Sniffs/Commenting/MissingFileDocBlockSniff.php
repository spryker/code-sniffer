<?php

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;

/**
 * Checks if file doc block exists
 */
class MissingFileDocBlockSniff extends AbstractFileDocBlockSniff
{

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        if (!$this->isSprykerNamespace($phpCsFile, $stackPointer)) {
            return;
        }

        if (!$this->existsFileDocBlock($phpCsFile, $stackPointer)) {
            $this->addFixableMissingDocblock($phpCsFile, $stackPointer);
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
        $fix = $phpCsFile->addFixableError(basename($phpCsFile->getFilename()) . ' has no File Doc Block.', $stackPointer);
        if ($fix) {
            $this->addFileDocBlock($phpCsFile, 0);
        }
    }

}
