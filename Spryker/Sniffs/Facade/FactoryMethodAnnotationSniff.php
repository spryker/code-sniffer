<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
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

        $bundle = $this->getModule($phpCsFile);
        $factoryName = $bundle . 'BusinessFactory';

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
    protected function hasFactoryAnnotation(File $phpCsFile, $stackPointer)
    {
        $position = $phpCsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPointer);
        $tokens = $phpCsFile->getTokens();

        while ($position !== false) {
            $position = $phpCsFile->findPrevious(T_DOC_COMMENT_TAG, $position);
            if ($position !== false) {
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
    protected function addFactoryAnnotation(File $phpCsFile, $stackPointer, $factoryName)
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
            $position = $phpCsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPointer);
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
    protected function getFactoryClassName(File $phpCsFile)
    {
        $className = $this->getClassName($phpCsFile);
        $classNameParts = explode('\\', $className);
        array_pop($classNameParts);
        if (!isset($classNameParts[2])) {
            return null;
        }

        $bundleName = $classNameParts[2];
        array_push($classNameParts, $bundleName . 'BusinessFactory');
        $factoryClassName = implode('\\', $classNameParts);

        return $factoryClassName;
    }
}
