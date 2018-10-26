<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Console;

use PHP_CodeSniffer\Files\File;

/**
 * Spryker Console classes should have a getQueryContainer() annotation.
 */
class QueryContainerMethodAnnotationSniff extends AbstractConsoleMethodAnnotationSniff
{
    protected const LAYER_PERSISTENCE = 'Persistence';

    /**
     * @inheritdoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        if (!$this->isConsole($phpCsFile, $stackPointer)) {
            return;
        }

        $bundle = $this->getModule($phpCsFile);
        $queryContainerName = $bundle . 'QueryContainer';

        if (!$this->hasQueryContainerAnnotation($phpCsFile, $stackPointer)
            && $this->fileExists($phpCsFile, $this->getQueryContainerInterfaceName($phpCsFile))
        ) {
            $fix = $phpCsFile->addFixableError('getQueryContainer() annotation missing', $stackPointer, 'Missing');
            if ($fix) {
                $this->addQueryContainerAnnotation($phpCsFile, $stackPointer, $queryContainerName);
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasQueryContainerAnnotation(File $phpCsFile, int $stackPointer): bool
    {
        $position = $phpCsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPointer);
        $tokens = $phpCsFile->getTokens();

        while ($position !== false) {
            $position = $phpCsFile->findPrevious(T_DOC_COMMENT_TAG, $position);
            if ($position !== false) {
                if (strpos($tokens[$position + 2]['content'], 'getQueryContainer()') !== false) {
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
     * @param string $queryContainerName
     *
     * @return void
     */
    protected function addQueryContainerAnnotation(File $phpCsFile, int $stackPointer, string $queryContainerName): void
    {
        $phpCsFile->fixer->beginChangeset();

        if ($this->getLayer($phpCsFile) !== static::LAYER_PERSISTENCE) {
            $this->addUseStatements(
                $phpCsFile,
                $stackPointer,
                [$this->getQueryContainerInterfaceName($phpCsFile)]
            );
        }

        if (!$this->hasDocBlock($phpCsFile, $stackPointer)) {
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, ' */');
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, ' * @method ' . $queryContainerName . 'Interface getQueryContainer()');
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, '/**');
        } else {
            $position = $phpCsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPointer);
            $phpCsFile->fixer->addNewlineBefore($position);
            $phpCsFile->fixer->addContentBefore($position, ' * @method ' . $queryContainerName . 'Interface getQueryContainer()');
        }

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getQueryContainerInterfaceName(File $phpCsFile): string
    {
        $className = $this->getClassName($phpCsFile);
        $classNameParts = explode('\\', $className);
        $classNameParts = array_slice($classNameParts, 0, 3);
        $bundleName = $classNameParts[2];
        array_push($classNameParts, static::LAYER_PERSISTENCE);
        array_push($classNameParts, $bundleName . 'QueryContainerInterface');
        $queryContainerInterfaceName = implode('\\', $classNameParts);

        return $queryContainerInterfaceName;
    }
}
