<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Sniffs\Plugin;

use PHP_CodeSniffer\Files\File;

/**
 * Spryker Plugin classes should have a getFactory() annotation.
 */
class FactoryMethodAnnotationSniff extends AbstractPluginMethodAnnotationSniff
{
    /**
     * @inheritdoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        if (!$this->isPlugin($phpCsFile, $stackPointer)) {
            return;
        }

        $bundle = $this->getModule($phpCsFile);
        $factoryName = $bundle . 'CommunicationFactory';
        if (!$this->hasFactoryAnnotation($phpCsFile, $stackPointer) && $this->fileExists($phpCsFile, $this->getFactoryClassName($phpCsFile))) {
            $fix = $phpCsFile->addFixableError('getFactory() annotation missing', $stackPointer, 'Missing');
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

        $this->addUseStatements(
            $phpCsFile,
            $stackPointer,
            [$this->getFactoryClassName($phpCsFile)]
        );

        $stackPointer = $this->getStackPointerOfClassBegin($phpCsFile, $stackPointer);

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
            $phpCsFile->fixer->addContentBefore($position, ' * @method ' . $factoryName . ' getFactory()');
        }

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getFactoryClassName(File $phpCsFile): string
    {
        $className = $this->getClassName($phpCsFile);
        $classNameParts = explode('\\', $className);
        $classNameParts = array_slice($classNameParts, 0, 4);
        $bundleName = $classNameParts[2];
        array_push($classNameParts, $bundleName . 'CommunicationFactory');
        $factoryClassName = implode('\\', $classNameParts);

        return $factoryClassName;
    }
}
