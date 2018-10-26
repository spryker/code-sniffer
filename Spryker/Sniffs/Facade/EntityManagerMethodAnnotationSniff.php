<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Facade;

use PHP_CodeSniffer\Files\File;

/**
 * Spryker Facade classes should have a getEntityManager() annotation.
 */
class EntityManagerMethodAnnotationSniff extends AbstractFacadeMethodAnnotationSniff
{
    protected const LAYER_PERSISTENCE = 'Persistence';

    /**
     * @inheritdoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        if (!$this->isFacade($phpCsFile)) {
            return;
        }

        $bundle = $this->getModule($phpCsFile);
        $entityManagerName = $bundle . 'EntityManager';

        if (!$this->hasEntityManagerAnnotation($phpCsFile, $stackPointer)
            && $this->fileExists($phpCsFile, $this->getEntityManagerInterfaceName($phpCsFile))
        ) {
            $fix = $phpCsFile->addFixableError('getEntityManager() annotation missing', $stackPointer, 'Missing');
            if ($fix) {
                $this->addEntityManagerAnnotation($phpCsFile, $stackPointer, $entityManagerName);
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasEntityManagerAnnotation(File $phpCsFile, int $stackPointer): bool
    {
        $position = $phpCsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPointer);
        $tokens = $phpCsFile->getTokens();

        while ($position !== false) {
            $position = $phpCsFile->findPrevious(T_DOC_COMMENT_TAG, $position);
            if ($position !== false) {
                if (strpos($tokens[$position + 2]['content'], 'getEntityManager()') !== false) {
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
     * @param string $entityManagerName
     *
     * @return void
     */
    protected function addEntityManagerAnnotation(File $phpCsFile, int $stackPointer, string $entityManagerName): void
    {
        $phpCsFile->fixer->beginChangeset();

        if ($this->getLayer($phpCsFile) !== static::LAYER_PERSISTENCE) {
            $this->addUseStatements(
                $phpCsFile,
                $stackPointer,
                [$this->getEntityManagerInterfaceName($phpCsFile)]
            );
        }

        if (!$this->hasDocBlock($phpCsFile, $stackPointer)) {
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, ' */');
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, ' * @method ' . $entityManagerName . 'Interface getEntityManager()');
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, '/**');
        } else {
            $position = $phpCsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPointer);
            $phpCsFile->fixer->addNewlineBefore($position);
            $phpCsFile->fixer->addContentBefore($position, ' * @method ' . $entityManagerName . 'Interface getEntityManager()');
        }

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getEntityManagerInterfaceName(File $phpCsFile): string
    {
        $className = $this->getClassName($phpCsFile);
        $classNameParts = explode('\\', $className);
        $classNameParts = array_slice($classNameParts, 0, 3);
        $bundleName = $classNameParts[2];
        array_push($classNameParts, static::LAYER_PERSISTENCE);
        array_push($classNameParts, $bundleName . 'EntityManagerInterface');
        $entityManagerInterfaceName = implode('\\', $classNameParts);

        return $entityManagerInterfaceName;
    }
}
