<?php

namespace Spryker\Sniffs\AbstractSniffs;

use PHP_CodeSniffer_File;

abstract class AbstractMethodAnnotationSniff extends AbstractSprykerSniff
{

    /**
     * @var bool
     */
    protected $fileExists = false;

    /**
     * @inheritdoc
     */
    public function register()
    {
        return [
            T_CLASS,
        ];
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasDocBlock(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $tokens = $phpCsFile->getTokens();

        return ($tokens[$stackPointer - 2]['type'] === 'T_DOC_COMMENT_CLOSE_TAG');
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param string $className
     *
     * @return bool
     */
    protected function fileExists(PHP_CodeSniffer_File $phpCsFile, $className)
    {
        $fileName = $phpCsFile->getFilename();
        $fileNameParts = explode(DIRECTORY_SEPARATOR, $fileName);
        $sourceDirectoryPosition = array_search('src', $fileNameParts);
        $basePathParts = array_slice($fileNameParts, 0, $sourceDirectoryPosition + 1);

        $basePath = implode(DIRECTORY_SEPARATOR, $basePathParts) . DIRECTORY_SEPARATOR;
        $classFileName = str_replace('\\', DIRECTORY_SEPARATOR, $className);

        $fileName = $basePath . $classFileName . '.php';

        return file_exists($fileName);
    }

}
