<?php

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer_File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

abstract class AbstractFileDocBlockSniff extends AbstractSprykerSniff
{

    const EXPECTED_COMMENT_FIRST_LINE_STRING = 'Copyright © %s-present Spryker Systems GmbH. All rights reserved.';
    const EXPECTED_COMMENT_SECOND_LINE_STRING = 'Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.';

    const SPRYKER_NAMESPACE = 'Spryker';
    const YEAR = '2016';

    /**
     * @var array
     */
    protected $sprykerTestNamespaces = [
        'Unit',
        'Functional',
    ];

    /**
     * @var array
     */
    protected $sprykerApplications = [
        'Client',
        'Shared',
        'Yves',
        'Zed',
    ];

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
    protected function isSprykerNamespace(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $firstNamespaceTokenPosition = $phpCsFile->findNext(T_STRING, $stackPointer);
        if ($firstNamespaceTokenPosition) {
            $firstNamespaceString = $phpCsFile->getTokens()[$firstNamespaceTokenPosition]['content'];
            $secondNamespaceTokenPosition = $phpCsFile->findNext(T_STRING, $firstNamespaceTokenPosition + 1);

            if (!$secondNamespaceTokenPosition) {
                return false;
            }

            $secondNamespaceString = $phpCsFile->getTokens()[$secondNamespaceTokenPosition]['content'];

            $isSprykerClass = ($firstNamespaceString === static::SPRYKER_NAMESPACE && in_array($secondNamespaceString, $this->sprykerApplications));
            $isSprykerTestClass = (in_array($firstNamespaceString, $this->sprykerTestNamespaces) && $secondNamespaceString === static::SPRYKER_NAMESPACE);

            return ($isSprykerClass || $isSprykerTestClass);
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function existsFileDocBlock(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
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
    protected function addFileDocBlock(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $phpCsFile->fixer->beginChangeset();

        $this->clearFileDocBlock($phpCsFile, $stackPointer);

        $phpCsFile->fixer->addNewline($stackPointer);
        $phpCsFile->fixer->addContent($stackPointer, '/**');
        $phpCsFile->fixer->addNewline($stackPointer);
        $phpCsFile->fixer->addContent($stackPointer, ' * ' . sprintf(static::EXPECTED_COMMENT_FIRST_LINE_STRING, static::YEAR));
        $phpCsFile->fixer->addNewline($stackPointer);
        $phpCsFile->fixer->addContent($stackPointer, ' * ' . static::EXPECTED_COMMENT_SECOND_LINE_STRING);
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
    protected function clearFileDocBlock(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
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
