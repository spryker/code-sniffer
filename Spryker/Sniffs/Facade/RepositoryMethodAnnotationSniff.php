<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Facade;

use PHP_CodeSniffer\Files\File;

/**
 * Spryker Facade classes should have a getRepository() annotation.
 */
class RepositoryMethodAnnotationSniff extends AbstractFacadeMethodAnnotationSniff
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
        $repositoryName = $bundle . 'Repository';

        if (!$this->hasRepositoryAnnotation($phpCsFile, $stackPointer)
            && $this->fileExists($phpCsFile, $this->getRepositoryInterfaceName($phpCsFile))
        ) {
            $fix = $phpCsFile->addFixableError('getRepository() annotation missing', $stackPointer, 'Missing');
            if ($fix) {
                $this->addRepositoryAnnotation($phpCsFile, $stackPointer, $repositoryName);
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasRepositoryAnnotation(File $phpCsFile, int $stackPointer): bool
    {
        $position = $phpCsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPointer);
        $tokens = $phpCsFile->getTokens();

        while ($position !== false) {
            $position = $phpCsFile->findPrevious(T_DOC_COMMENT_TAG, $position);
            if ($position !== false) {
                if (strpos($tokens[$position + 2]['content'], 'getRepository()') !== false) {
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
     * @param string $repositoryName
     *
     * @return void
     */
    protected function addRepositoryAnnotation(File $phpCsFile, int $stackPointer, string $repositoryName): void
    {
        $phpCsFile->fixer->beginChangeset();

        if ($this->getLayer($phpCsFile) !== static::LAYER_PERSISTENCE) {
            $this->addUseStatements(
                $phpCsFile,
                $stackPointer,
                [$this->getRepositoryInterfaceName($phpCsFile)]
            );
        }

        if (!$this->hasDocBlock($phpCsFile, $stackPointer)) {
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, ' */');
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, ' * @method ' . $repositoryName . 'Interface getRepository()');
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, '/**');
        } else {
            $position = $phpCsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPointer);
            $phpCsFile->fixer->addNewlineBefore($position);
            $phpCsFile->fixer->addContentBefore($position, ' * @method ' . $repositoryName . 'Interface getRepository()');
        }

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getRepositoryInterfaceName(File $phpCsFile): string
    {
        $className = $this->getClassName($phpCsFile);
        $classNameParts = explode('\\', $className);
        $classNameParts = array_slice($classNameParts, 0, 3);
        $bundleName = $classNameParts[2];
        array_push($classNameParts, static::LAYER_PERSISTENCE);
        array_push($classNameParts, $bundleName . 'RepositoryInterface');
        $repositoryInterfaceName = implode('\\', $classNameParts);

        return $repositoryInterfaceName;
    }
}
