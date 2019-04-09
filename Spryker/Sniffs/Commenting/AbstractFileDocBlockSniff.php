<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

abstract class AbstractFileDocBlockSniff extends AbstractSprykerSniff
{
    protected const EXPECTED_COMMENT_FIRST_LINE_STRING = 'Copyright © %s-present Spryker Systems GmbH. All rights reserved.';
    protected const EXPECTED_COMMENT_SECOND_LINE_STRING = 'Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.';

    protected const SPRYKER_NAMESPACE = 'Spryker';
    protected const YEAR = '2016';

    /**
     * @var array
     */
    protected $sprykerNamespaces = [
        'Spryker',
        'SprykerShop',
        'SprykerEco',
        'SprykerSdk',
    ];

    /**
     * @var array
     */
    protected $sprykerTestNamespaces = [
        'SprykerTest',
        'SprykerShopTest',
        'SprykerEcoTest',
        'SprykerSdkTest',
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
    public function register(): array
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
    protected function getLicense(string $path): string
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
    protected function isSprykerNamespace(File $phpCsFile, int $stackPointer): bool
    {
        $firstNamespaceTokenPosition = $phpCsFile->findNext(T_STRING, $stackPointer);
        if ($firstNamespaceTokenPosition) {
            $firstNamespaceString = $phpCsFile->getTokens()[$firstNamespaceTokenPosition]['content'];
            $secondNamespaceTokenPosition = $phpCsFile->findNext(T_STRING, $firstNamespaceTokenPosition + 1);

            if (!$secondNamespaceTokenPosition) {
                return false;
            }

            $secondNamespaceString = $phpCsFile->getTokens()[$secondNamespaceTokenPosition]['content'];
            $isSprykerClass = (in_array($firstNamespaceString, $this->sprykerNamespaces) && in_array($secondNamespaceString, $this->sprykerApplications));
            $isSprykerTestClass = in_array($firstNamespaceString, $this->sprykerTestNamespaces) && in_array($secondNamespaceString, $this->sprykerApplications);

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
    protected function existsFileDocBlock(File $phpCsFile, int $stackPointer): bool
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
    protected function addFileDocBlock(File $phpCsFile, int $stackPointer): void
    {
        $phpCsFile->fixer->beginChangeset();

        $this->clearFileDocBlock($phpCsFile, $stackPointer);

        $tokens = $phpCsFile->getTokens();
        $line = $tokens[$stackPointer]['line'];
        $fileDocBlockStartPosition = $stackPointer;
        while ($tokens[$fileDocBlockStartPosition + 1]['line'] === $line) {
            $fileDocBlockStartPosition++;
        }

        $phpCsFile->fixer->addNewline($fileDocBlockStartPosition);
        $phpCsFile->fixer->addContent($fileDocBlockStartPosition, '/**');
        $phpCsFile->fixer->addNewline($fileDocBlockStartPosition);
        $phpCsFile->fixer->addContent($fileDocBlockStartPosition, ' * ' . sprintf(static::EXPECTED_COMMENT_FIRST_LINE_STRING, static::YEAR));
        $phpCsFile->fixer->addNewline($fileDocBlockStartPosition);
        $phpCsFile->fixer->addContent($fileDocBlockStartPosition, ' * ' . static::EXPECTED_COMMENT_SECOND_LINE_STRING);
        $phpCsFile->fixer->addNewline($fileDocBlockStartPosition);
        $phpCsFile->fixer->addContent($fileDocBlockStartPosition, ' */');
        $phpCsFile->fixer->addNewline($fileDocBlockStartPosition);
        $phpCsFile->fixer->addNewline($fileDocBlockStartPosition);

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function clearFileDocBlock(File $phpCsFile, int $stackPointer): void
    {
        $openTagIndex = $phpCsFile->findPrevious(T_OPEN_TAG, $stackPointer);

        $tokens = $phpCsFile->getTokens();
        $line = $tokens[$openTagIndex]['line'];
        $fileDocBlockStartPosition = $openTagIndex;
        while ($tokens[$fileDocBlockStartPosition + 1]['line'] === $line) {
            $fileDocBlockStartPosition++;
        }
        $fileDocBlockStartPosition++;

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
    protected function checkCustomFileDocBlock(File $phpCsFile, int $stackPointer): void
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
            $fix = $phpCsFile->addFixableError('No file doc block', $stackPointer, 'CustomFileDocBlockMissing');
            if ($fix) {
                $this->addFileDocBlock($phpCsFile, 0);
            }

            return;
        }

        $this->assertNewlineBefore($phpCsFile, $stackPointer);

        if ($this->isCustomFileDocBlock($phpCsFile, $stackPointer, $license)) {
            return;
        }

        $fix = $phpCsFile->addFixableError('Wrong file doc block', $stackPointer, 'CustomFileDocBlockWrong');
        if ($fix) {
            $this->addCustomFileDocBlock($phpCsFile, 0, $license);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function assertNewlineBefore(File $phpCsFile, int $stackPointer): void
    {
        $fileDocBlockStartPosition = $phpCsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $stackPointer);

        $tokens = $phpCsFile->getTokens();

        $prevIndex = $phpCsFile->findPrevious(T_WHITESPACE, $fileDocBlockStartPosition - 1, 0, true);

        if ($tokens[$prevIndex]['line'] === $tokens[$fileDocBlockStartPosition]['line'] - 2) {
            return;
        }

        $fix = $phpCsFile->addFixableError('Whitespace issue around file doc block', $stackPointer, 'FileDocBlockSpacing');
        if (!$fix) {
            return;
        }

        $phpCsFile->fixer->beginChangeset();

        if ($tokens[$prevIndex]['line'] > $tokens[$fileDocBlockStartPosition]['line'] - 2) {
            $phpCsFile->fixer->addNewline($prevIndex);
        } else {
            $index = $prevIndex;
            while ($index < $fileDocBlockStartPosition - 1) {
                $index++;
                $phpCsFile->fixer->replaceToken($index, '');
            }
        }

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     * @param string $license
     *
     * @return bool
     */
    protected function isCustomFileDocBlock(File $phpCsFile, int $stackPointer, string $license): bool
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
    protected function getFileDocBlockTokens(File $phpCsFile, int $stackPointer): array
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
    protected function addCustomFileDocBlock(File $phpCsFile, int $stackPointer, string $license): void
    {
        $phpCsFile->fixer->beginChangeset();

        $this->clearFileDocBlock($phpCsFile, $stackPointer);

        $tokens = $phpCsFile->getTokens();
        $line = $tokens[$stackPointer]['line'];
        $fileDocBlockStartPosition = $stackPointer;
        while ($tokens[$fileDocBlockStartPosition + 1]['line'] === $line) {
            $fileDocBlockStartPosition++;
        }

        $phpCsFile->fixer->addNewline($fileDocBlockStartPosition);
        $phpCsFile->fixer->addContent($fileDocBlockStartPosition, $license);
        $phpCsFile->fixer->addNewline($fileDocBlockStartPosition);

        $phpCsFile->fixer->endChangeset();
    }
}
