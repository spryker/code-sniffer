<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\AbstractSniffs;

use PHP_CodeSniffer\Files\File;

abstract class AbstractClassDetectionSprykerSniff extends AbstractSprykerSniff
{
    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     * @param string $abstractName
     *
     * @return bool
     */
    protected function extendsAbstract(File $phpCsFile, int $stackPointer, string $abstractName): bool
    {
        $extendedClassName = $phpCsFile->findExtendedClassName($stackPointer);

        // We do not force-annotate on abstract classes
        $abstractClassTypeIndex = $phpCsFile->findPrevious(T_ABSTRACT, $stackPointer - 1);

        if ($abstractClassTypeIndex !== false) {
            return false;
        }

        if ($extendedClassName === $abstractName) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param string $predefinedName
     *
     * @return bool
     */
    protected function hasCorrectName(File $phpCsFile, string $predefinedName): bool
    {
        $className = $this->getClassName($phpCsFile);
        $moduleName = $this->getModule($phpCsFile);

        $correctName = $moduleName . $predefinedName;
        $stringLength = strlen($correctName);
        $relevantClassNamePart = substr($className, -$stringLength);

        return $relevantClassNamePart === $correctName;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isProvider(File $phpCsFile): bool
    {
        return $this->hasCorrectName($phpCsFile, 'DependencyProvider') && $this->isCoreProvider($phpCsFile);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isCoreProvider(File $phpCsFile): bool
    {
        $namespace = $this->getNamespace($phpCsFile);

        return ($namespace === static::NAMESPACE_SPRYKER);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isController(File $phpCsFile, int $stackPointer): bool
    {
        return $this->extendsAbstract($phpCsFile, $stackPointer, 'AbstractController');
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isCollectionType(File $phpCsFile, int $stackPointer): bool
    {
        return $this->extendsAbstract($phpCsFile, $stackPointer, 'AbstractCollectionType');
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isConsole(File $phpCsFile, int $stackPointer): bool
    {
        return $this->extendsAbstract($phpCsFile, $stackPointer, 'Console');
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isFacade(File $phpCsFile, int $stackPointer): bool
    {
        return $this->hasCorrectName($phpCsFile, 'Facade') &&
            $this->extendsAbstract($phpCsFile, $stackPointer, 'AbstractFacade');
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isFactory(File $phpCsFile, int $stackPointer): bool
    {
        if ($this->isBusinessFactory($phpCsFile, $stackPointer)) {
            return true;
        }

        if ($this->isCommunicationFactory($phpCsFile, $stackPointer)) {
            return true;
        }

        if ($this->isPersistenceFactory($phpCsFile, $stackPointer)) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isBusinessFactory(File $phpCsFile, int $stackPointer): bool
    {
        return $this->hasCorrectName($phpCsFile, 'BusinessFactory') &&
            $this->extendsAbstract($phpCsFile, $stackPointer, 'AbstractBusinessFactory');
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isCommunicationFactory(File $phpCsFile, int $stackPointer): bool
    {
        return $this->hasCorrectName($phpCsFile, 'CommunicationFactory') &&
            $this->extendsAbstract($phpCsFile, $stackPointer, 'AbstractCommunicationFactory');
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isPersistenceFactory(File $phpCsFile, int $stackPointer): bool
    {
        return $this->hasCorrectName($phpCsFile, 'PersistenceFactory') &&
            $this->extendsAbstract($phpCsFile, $stackPointer, 'AbstractPersistenceFactory');
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isPlugin(File $phpCsFile, int $stackPointer): bool
    {
        if (!$this->isFileInPluginDirectory($phpCsFile)) {
            return false;
        }

        if (!$this->extendsAbstract($phpCsFile, $stackPointer, 'AbstractPlugin')) {
            return false;
        }

        return true;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isFileInPluginDirectory(File $phpCsFile): bool
    {
        return (bool)preg_match('/Communication\/Plugin/', $phpCsFile->getFilename());
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isType(File $phpCsFile, int $stackPointer): bool
    {
        return $this->extendsAbstract($phpCsFile, $stackPointer, 'AbstractType');
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isQueryContainer(File $phpCsFile, int $stackPointer): bool
    {
        return $this->hasCorrectName($phpCsFile, 'QueryContainer') &&
            $this->extendsAbstract($phpCsFile, $stackPointer, 'AbstractQueryContainer');
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isRepository(File $phpCsFile, int $stackPointer): bool
    {
        return $this->hasCorrectName($phpCsFile, 'Repository') &&
            $this->extendsAbstract($phpCsFile, $stackPointer, 'AbstractRepository');
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isEntityManager(File $phpCsFile, int $stackPointer): bool
    {
        return $this->hasCorrectName($phpCsFile, 'EntityManager') &&
            $this->extendsAbstract($phpCsFile, $stackPointer, 'AbstractEntityManager');
    }
}
