<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
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
        'Acceptance',
    ];

    /**
     * @var array
     */
    protected $sprykerApplications = [
        'Client',
        'Shared',
        'Yves',
        'Zed',
        'Service',
    ];

    /**
     * Cache of licenses to avoid file lookups.
     *
     * @var array
     */
    protected $licenseMap = [];

    /**
     * @return array
     */
    public function register()
    {
        return [
            T_NAMESPACE,
        ];
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function getLicense($path)
    {
        if (isset($this->licenseMap[$path])) {
            return $this->licenseMap[$path];
        }

        if (!file_exists($path . '.license')) {
            $this->licenseMap[$path] = '';
            return '';
        }

        $license = (string)file_get_contents($path . '.license');
        $this->licenseMap[$path] = $license;

        return $license;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isSprykerNamespace(File $phpCsFile, $stackPointer)
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
            $isSprykerTestClass = (in_array($firstNamespaceString, $this->sprykerTestNamespaces) && ($secondNamespaceString === static::SPRYKER_NAMESPACE));

            return ($isSprykerClass || $isSprykerTestClass);
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function existsFileDocBlock(File $phpCsFile, $stackPointer)
    {
        $fileDocBlockStartPosition = $phpCsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $stackPointer);

        return ($fileDocBlockStartPosition !== false);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function addFileDocBlock(File $phpCsFile, $stackPointer)
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
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function clearFileDocBlock(File $phpCsFile, $stackPointer)
    {
        $fileDocBlockStartPosition = $phpCsFile->findPrevious(T_OPEN_TAG, $stackPointer) + 1;

        $currentPosition = $fileDocBlockStartPosition;
        $endPosition = $phpCsFile->findNext([T_NAMESPACE], $currentPosition);
        while ($currentPosition < $endPosition) {
            $phpCsFile->fixer->replaceToken($currentPosition, '');
            $currentPosition++;
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
        $path = str_replace(getcwd(), '', $phpCsFile->getFilename());
        if (strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) === 0) {
            $pathArray = explode(DIRECTORY_SEPARATOR, substr($path, 8));

            $path = getcwd() . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR
                . array_shift($pathArray) . DIRECTORY_SEPARATOR . array_shift($pathArray) . DIRECTORY_SEPARATOR;
        } else {
            $path = getcwd() . DIRECTORY_SEPARATOR;
        }

        $license = $this->getLicense($path);
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
     * @param string $license
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
