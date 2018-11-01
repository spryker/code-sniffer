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
     *
     * @return bool
     */
    protected function isProvider(File $phpCsFile): bool
    {
        $className = $this->getClassName($phpCsFile);
        $moduleName = $this->getModule($phpCsFile);

        $providerName = $moduleName . 'DependencyProvider';
        $stringLength = strlen($providerName);
        $relevantClassNamePart = substr($className, -$stringLength);

        return ($relevantClassNamePart === $providerName) && $this->isCoreProvider($phpCsFile);
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
        return $this->extendsAbstractController($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function extendsAbstractController(File $phpCsFile, int $stackPointer): bool
    {
        $extendedClassName = $phpCsFile->findExtendedClassName($stackPointer);

        // We do not force-annotate on abstract classes
        $abstractClassTypeIndex = $phpCsFile->findPrevious(T_ABSTRACT, $stackPointer - 1);

        if ($abstractClassTypeIndex !== false) {
            return false;
        }

        if ($extendedClassName === 'AbstractController') {
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
    protected function isCollectionType(File $phpCsFile, int $stackPointer): bool
    {
        return $this->extendsAbstractCollectionType($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function extendsAbstractCollectionType(File $phpCsFile, int $stackPointer): bool
    {
        $extendedClassName = $phpCsFile->findExtendedClassName($stackPointer);

        // We do not force-annotate on abstract classes
        $abstractClassTypeIndex = $phpCsFile->findPrevious(T_ABSTRACT, $stackPointer - 1);
        if ($abstractClassTypeIndex !== false) {
            return false;
        }

        if ($extendedClassName === 'AbstractCollectionType') {
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
    protected function isConsole(File $phpCsFile, int $stackPointer): bool
    {
        return $this->extendsConsole($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function extendsConsole(File $phpCsFile, int $stackPointer): bool
    {
        $extendedClassName = $phpCsFile->findExtendedClassName($stackPointer);

        // We do not force-annotate on abstract classes
        $abstractClassTypeIndex = $phpCsFile->findPrevious(T_ABSTRACT, $stackPointer - 1);
        if ($abstractClassTypeIndex !== false) {
            return false;
        }

        if ($extendedClassName === 'Console') {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isFacade(File $phpCsFile): bool
    {
        $className = $this->getClassName($phpCsFile);
        $moduleName = $this->getModule($phpCsFile);

        $facadeName = $moduleName . 'Facade';
        $stringLength = strlen($facadeName);
        $relevantClassNamePart = substr($className, -$stringLength);

        return ($relevantClassNamePart === $facadeName);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isFactory(File $phpCsFile): bool
    {
        if ($this->isBusinessFactory($phpCsFile)) {
            return true;
        }

        if ($this->isCommunicationFactory($phpCsFile)) {
            return true;
        }

        if ($this->isPersistenceFactory($phpCsFile)) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isBusinessFactory(File $phpCsFile): bool
    {
        return substr($this->getClassName($phpCsFile), -15) === 'BusinessFactory';
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isCommunicationFactory(File $phpCsFile): bool
    {
        return substr($this->getClassName($phpCsFile), -20) === 'CommunicationFactory';
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isPersistenceFactory(File $phpCsFile): bool
    {
        return substr($this->getClassName($phpCsFile), -18) === 'PersistenceFactory';
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isPlugin(File $phpCsFile, int $stackPointer): bool
    {
        if ($this->isFileInPluginDirectory($phpCsFile) && $this->extendsAbstractPlugin($phpCsFile, $stackPointer)) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return int
     */
    protected function isFileInPluginDirectory(File $phpCsFile): int
    {
        return preg_match('/Communication\/Plugin/', $phpCsFile->getFilename());
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function extendsAbstractPlugin(File $phpCsFile, int $stackPointer): bool
    {
        $extendedClassName = $phpCsFile->findExtendedClassName($stackPointer);

        // We do not force-annotate on abstract classes
        $abstractClassTypeIndex = $phpCsFile->findPrevious(T_ABSTRACT, $stackPointer - 1);
        if ($abstractClassTypeIndex !== false) {
            return false;
        }

        if ($extendedClassName === 'AbstractPlugin') {
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
    protected function isType(File $phpCsFile, int $stackPointer): bool
    {
        return $this->extendsAbstractType($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function extendsAbstractType(File $phpCsFile, int $stackPointer): bool
    {
        $extendedClassName = $phpCsFile->findExtendedClassName($stackPointer);

        // We do not force-annotate on abstract classes
        $abstractClassTypeIndex = $phpCsFile->findPrevious(T_ABSTRACT, $stackPointer - 1);
        if ($abstractClassTypeIndex !== false) {
            return false;
        }

        if ($extendedClassName === 'AbstractType') {
            return true;
        }

        return false;
    }
}
