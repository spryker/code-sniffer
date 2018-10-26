<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\CollectionType;

use PHP_CodeSniffer\Files\File;

/**
 * Spryker CollectionType classes should have a getConfig() annotation.
 */
class ConfigMethodAnnotationSniff extends AbstractCollectionTypeMethodAnnotationSniff
{
    /**
     * @inheritdoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        if (!$this->isCollectionType($phpCsFile, $stackPointer)) {
            return;
        }

        $module = $this->getModule($phpCsFile);
        $configName = $module . 'Config';

        if (!$this->hasConfigAnnotation($phpCsFile, $stackPointer) && $this->fileExists($phpCsFile, $this->getConfigClassName($phpCsFile))) {
            $fix = $phpCsFile->addFixableError('getConfig() annotation missing', $stackPointer, 'Missing');
            if ($fix) {
                $this->addConfigAnnotation($phpCsFile, $stackPointer, $configName);
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasConfigAnnotation(File $phpCsFile, int $stackPointer): bool
    {
        $position = $phpCsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPointer);
        $tokens = $phpCsFile->getTokens();

        while ($position !== false) {
            $position = $phpCsFile->findPrevious(T_DOC_COMMENT_TAG, $position);
            if ($position !== false) {
                if (strpos($tokens[$position + 2]['content'], 'getConfig()') !== false) {
                    return true;
                }
                $position--;
            }
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     * @param string $configName
     *
     * @return void
     */
    protected function addConfigAnnotation(File $phpCsFile, int $stackPointer, string $configName): void
    {
        $phpCsFile->fixer->beginChangeset();

        $this->addUseStatements(
            $phpCsFile,
            $stackPointer,
            [$this->getConfigClassName($phpCsFile)]
        );

        if (!$this->hasDocBlock($phpCsFile, $stackPointer)) {
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, ' */');
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, ' * @method ' . $configName . ' getConfig()');
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, '/**');
        } else {
            $position = $phpCsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPointer);
            $phpCsFile->fixer->addNewlineBefore($position);
            $phpCsFile->fixer->addContentBefore($position, ' * @method ' . $configName . ' getConfig()');
        }

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getConfigClassName(File $phpCsFile): string
    {
        $className = $this->getClassName($phpCsFile);
        $classNameParts = explode('\\', $className);
        $classNameParts = array_slice($classNameParts, 0, 3);
        $moduleName = $classNameParts[2];
        array_push($classNameParts, $moduleName . 'Config');
        $configClassName = implode('\\', $classNameParts);

        return $configClassName;
    }
}
