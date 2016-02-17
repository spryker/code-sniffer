<?php

namespace Spryker\Sniffs\Commenting;

abstract class AbstractFileDocBlockSniff implements \PHP_CodeSniffer_Sniff
{

    const EXPECTED_COMMENT_FIRST_LINE_STRING = '(c) Spryker Systems GmbH copyright protected';
    const EXPECTED_COMMENT_SECOND_LINE_STRING = 'CatFace';

    /**
     * @return array
     */
    public function register()
    {
        return [
            T_NAMESPACE
        ];
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param $stackPointer
     *
     * @return bool|int
     */
    protected function existsFileDocBlock(\PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $fileDocBlockStartPosition = $phpCsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $stackPointer);

        return ($fileDocBlockStartPosition !== false);
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param $stackPointer
     *
     * @return void
     */
    protected function addFileDocBlock(\PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $phpCsFile->fixer->beginChangeset();
        $phpCsFile->fixer->addNewline($stackPointer);
        $phpCsFile->fixer->addContent($stackPointer, '/**');
        $phpCsFile->fixer->addNewline($stackPointer);
        $phpCsFile->fixer->addContent($stackPointer, ' * ' . self::EXPECTED_COMMENT_FIRST_LINE_STRING);
        $phpCsFile->fixer->addNewline($stackPointer);
        $phpCsFile->fixer->addContent($stackPointer, ' * ' . self::EXPECTED_COMMENT_SECOND_LINE_STRING);
        $phpCsFile->fixer->addNewline($stackPointer);
        $phpCsFile->fixer->addContent($stackPointer, ' */');
        $phpCsFile->fixer->addNewline($stackPointer);
        $phpCsFile->fixer->endChangeset();
    }

}
