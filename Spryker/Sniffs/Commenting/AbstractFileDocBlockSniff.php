<?php

namespace Spryker\Sniffs\Commenting;

abstract class AbstractFileDocBlockSniff implements \PHP_CodeSniffer_Sniff
{

    const EXPECTED_COMMENT_FIRST_LINE_STRING = 'Copyright © %s-present Spryker Systems GmbH. All rights reserved.';
    const EXPECTED_COMMENT_SECOND_LINE_STRING = 'Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.';
    const SPRYKER_NAMESPACE = 'Spryker';

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
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isSprykerNamespace(\PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $sprykerNamespaceTokenPosition = $phpCsFile->findNext(T_STRING, $stackPointer);
        if ($sprykerNamespaceTokenPosition) {
            $sprykerNamespace = $phpCsFile->getTokens()[$sprykerNamespaceTokenPosition]['content'];

            return ($sprykerNamespace === self::SPRYKER_NAMESPACE);
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function existsFileDocBlock(\PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $fileDocBlockStartPosition = $phpCsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $stackPointer);

        return ($fileDocBlockStartPosition !== false);
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function addFileDocBlock(\PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $phpCsFile->fixer->beginChangeset();

        $this->clearFileDocBlock($phpCsFile, $stackPointer);

        $phpCsFile->fixer->addNewline($stackPointer);
        $phpCsFile->fixer->addContent($stackPointer, '/**');
        $phpCsFile->fixer->addNewline($stackPointer);
        $phpCsFile->fixer->addContent($stackPointer, ' * ' . sprintf(self::EXPECTED_COMMENT_FIRST_LINE_STRING, date('Y')));
        $phpCsFile->fixer->addNewline($stackPointer);
        $phpCsFile->fixer->addContent($stackPointer, ' * ' . self::EXPECTED_COMMENT_SECOND_LINE_STRING);
        $phpCsFile->fixer->addNewline($stackPointer);
        $phpCsFile->fixer->addContent($stackPointer, ' */');
        $phpCsFile->fixer->addNewline($stackPointer);
        $phpCsFile->fixer->addNewline($stackPointer);
        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    private function clearFileDocBlock(\PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $fileDocBlockStartPosition = $phpCsFile->findPrevious(T_OPEN_TAG, $stackPointer) + 1;

        $currentPosition = $fileDocBlockStartPosition;
        $endPosition = $phpCsFile->findNext([T_NAMESPACE], $currentPosition);
        do {
            $phpCsFile->fixer->replaceToken($currentPosition, '');
            $currentPosition++;
        } while ($currentPosition < $endPosition);
    }

}
