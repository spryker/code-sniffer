<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Plugin;

use PHP_CodeSniffer\Files\File;

/**
 * Spryker Plugin classes should have a getFacade() annotation.
 */
class FacadeMethodAnnotationSniff extends AbstractPluginMethodAnnotationSniff
{
    /**
     * @inheritdoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        if (!$this->isPlugin($phpCsFile, $stackPointer)) {
            return;
        }

        $module = $this->getModule($phpCsFile);
        $facadeName = $module . 'Facade';

        if (!$this->hasFacadeAnnotation($phpCsFile, $stackPointer)
            && $this->fileExists($phpCsFile, $this->getFacadeInterfaceName($phpCsFile))
        ) {
            $fix = $phpCsFile->addFixableError('getFacade() annotation missing', $stackPointer, 'Missing');
            if ($fix) {
                $this->addFacadeAnnotation($phpCsFile, $stackPointer, $facadeName);
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasFacadeAnnotation(File $phpCsFile, int $stackPointer): bool
    {
        $position = (int)$phpCsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPointer);
        $tokens = $phpCsFile->getTokens();

        while ($position) {
            $position = (int)$phpCsFile->findPrevious(T_DOC_COMMENT_TAG, $position);
            if ($position) {
                if (strpos($tokens[$position + 2]['content'], 'getFacade()') !== false) {
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
     * @param string $facadeName
     *
     * @return void
     */
    protected function addFacadeAnnotation(File $phpCsFile, int $stackPointer, string $facadeName): void
    {
        $phpCsFile->fixer->beginChangeset();

        $this->addUseStatements(
            $phpCsFile,
            $stackPointer,
            [$this->getFacadeInterfaceName($phpCsFile)]
        );

        $stackPointer = $this->getStackPointerOfClassBegin($phpCsFile, $stackPointer);

        if (!$this->hasDocBlock($phpCsFile, $stackPointer)) {
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, ' */');
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, ' * @method ' . $facadeName . 'Interface getFacade()');
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, '/**');
        } else {
            $position = (int)$phpCsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPointer);
            $phpCsFile->fixer->addNewlineBefore($position);
            $phpCsFile->fixer->addContentBefore($position, ' * @method ' . $facadeName . 'Interface getFacade()');
        }

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getFacadeInterfaceName(File $phpCsFile): string
    {
        $className = $this->getClassName($phpCsFile);
        $classNameParts = explode('\\', $className);
        $classNameParts = array_slice($classNameParts, 0, 3);
        $moduleName = $classNameParts[2];
        array_push($classNameParts, 'Business');
        array_push($classNameParts, $moduleName . 'FacadeInterface');
        $facadeInterfaceName = implode('\\', $classNameParts);

        return $facadeInterfaceName;
    }
}
