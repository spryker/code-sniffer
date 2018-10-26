<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Facade;

use PHP_CodeSniffer\Files\File;

/**
 * Spryker Facade classes should have a getFactory() annotation.
 */
class FactoryMethodAnnotationSniff extends AbstractFacadeMethodAnnotationSniff
{
    /**
     * @inheritdoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        if (!$this->isFacade($phpCsFile)) {
            return;
        }

        $module = $this->getModule($phpCsFile);
        $factoryName = $module . 'BusinessFactory';

        $className = $this->getFactoryClassName($phpCsFile);
        if (!$this->hasFactoryAnnotation($phpCsFile, $stackPointer) && $className && $this->fileExists($phpCsFile, $className)) {
            $fix = $phpCsFile->addFixableError('getFactory() annotation missing', $stackPointer, 'FactoryAnnotationMissing');
            if ($fix) {
                $this->addFactoryAnnotation($phpCsFile, $stackPointer, $factoryName);
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasFactoryAnnotation(File $phpCsFile, int $stackPointer): bool
    {
        $position = (int)$phpCsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPointer);
        $tokens = $phpCsFile->getTokens();

        while ($position) {
            $position = (int)$phpCsFile->findPrevious(T_DOC_COMMENT_TAG, $position);
            if ($position) {
                if (strpos($tokens[$position + 2]['content'], 'getFactory()') !== false) {
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
     * @param string $factoryName
     *
     * @return void
     */
    protected function addFactoryAnnotation(File $phpCsFile, int $stackPointer, string $factoryName): void
    {
        $phpCsFile->fixer->beginChangeset();

        if (!$this->hasDocBlock($phpCsFile, $stackPointer)) {
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, ' */');
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, ' * @method ' . $factoryName . ' getFactory()');
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, '/**');
        } else {
            $position = (int)$phpCsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPointer);
            $phpCsFile->fixer->addNewlineBefore($position);
            $phpCsFile->fixer->addContentBefore($position, ' * @method ' . $factoryName . ' getConfig()');
        }

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string|null
     */
    protected function getFactoryClassName(File $phpCsFile): ?string
    {
        $className = $this->getClassName($phpCsFile);
        $classNameParts = explode('\\', $className);
        array_pop($classNameParts);
        if (!isset($classNameParts[2])) {
            return null;
        }

        $moduleName = $classNameParts[2];
        array_push($classNameParts, $moduleName . 'BusinessFactory');
        $factoryClassName = implode('\\', $classNameParts);

        return $factoryClassName;
    }
}
