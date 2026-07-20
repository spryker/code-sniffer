<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\AbstractSniffs;

use PHP_CodeSniffer\Files\File;
use ReflectionClass;
use SlevomatCodingStandard\Helpers\NamespaceHelper;
use Throwable;

abstract class AbstractClassDetectionSprykerSniff extends AbstractSprykerSniff
{
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

        if (in_array($abstractName, $this->getParentClassesFor($phpCsFile, $stackPointer), true)) {
            return true;
        }

        return false;
    }

    /**
     * @return array<string>
     */
    protected function getParentClassesFor(File $file, int $stackPointer): array
    {
        $parents = [];
        $parentClass = null;

        $className = $file->getDeclarationName($stackPointer);
        $namespace = NamespaceHelper::findCurrentNamespaceName($file, $stackPointer);
        $fullName = sprintf(
            '%s%s%s',
            $namespace,
            NamespaceHelper::NAMESPACE_SEPARATOR,
            $className,
        );

        do {
            try {
                if (class_exists($fullName)) {
                    $reflection = new ReflectionClass($fullName);
                    $parentClass = $reflection->getParentClass();
                }
            } catch (Throwable $e) {
                break;
            }
            if (!$parentClass instanceof ReflectionClass) {
                break;
            }
            $parents[] = $parentClass->getShortName();
            $fullName = $parentClass->getName();
        } while ($parentClass);

        return array_filter($parents);
    }

    protected function hasCorrectName(File $phpCsFile, string $predefinedName): bool
    {
        $className = $this->getClassName($phpCsFile);
        $moduleName = $this->getModule($phpCsFile);

        $correctName = $moduleName . $predefinedName;
        $stringLength = strlen($correctName);
        $relevantClassNamePart = substr($className, -$stringLength);

        return $relevantClassNamePart === $correctName;
    }

    protected function isProvider(File $phpCsFile): bool
    {
        return $this->hasCorrectName($phpCsFile, 'DependencyProvider') && $this->isCore($phpCsFile);
    }

    protected function isController(File $phpCsFile, int $stackPointer): bool
    {
        return $this->extendsAbstract($phpCsFile, $stackPointer, 'AbstractController');
    }

    protected function isCollectionType(File $phpCsFile, int $stackPointer): bool
    {
        return $this->extendsAbstract($phpCsFile, $stackPointer, 'AbstractCollectionType');
    }

    protected function isConsole(File $phpCsFile, int $stackPointer): bool
    {
        return $this->extendsAbstract($phpCsFile, $stackPointer, 'Console');
    }

    protected function isFacade(File $phpCsFile, int $stackPointer): bool
    {
        return $this->hasCorrectName($phpCsFile, 'Facade') &&
        $this->extendsAbstract($phpCsFile, $stackPointer, 'AbstractFacade');
    }

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

    protected function isBusinessFactory(File $phpCsFile, int $stackPointer): bool
    {
        return $this->hasCorrectName($phpCsFile, 'BusinessFactory') &&
        $this->extendsAbstract($phpCsFile, $stackPointer, 'AbstractBusinessFactory');
    }

    protected function isCommunicationFactory(File $phpCsFile, int $stackPointer): bool
    {
        return $this->hasCorrectName($phpCsFile, 'CommunicationFactory') &&
        $this->extendsAbstract($phpCsFile, $stackPointer, 'AbstractCommunicationFactory');
    }

    protected function isPersistenceFactory(File $phpCsFile, int $stackPointer): bool
    {
        return $this->hasCorrectName($phpCsFile, 'PersistenceFactory') &&
        $this->extendsAbstract($phpCsFile, $stackPointer, 'AbstractPersistenceFactory');
    }

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

    protected function isFileInPluginDirectory(File $phpCsFile): bool
    {
        return (bool)preg_match('/Communication\/Plugin/', $phpCsFile->getFilename());
    }

    protected function isType(File $phpCsFile, int $stackPointer): bool
    {
        return $this->extendsAbstract($phpCsFile, $stackPointer, 'AbstractType');
    }

    protected function isQueryContainer(File $phpCsFile, int $stackPointer): bool
    {
        return $this->hasCorrectName($phpCsFile, 'QueryContainer') &&
        $this->extendsAbstract($phpCsFile, $stackPointer, 'AbstractQueryContainer');
    }

    protected function isRepository(File $phpCsFile, int $stackPointer): bool
    {
        return $this->hasCorrectName($phpCsFile, 'Repository') &&
        $this->extendsAbstract($phpCsFile, $stackPointer, 'AbstractRepository');
    }

    protected function isEntityManager(File $phpCsFile, int $stackPointer): bool
    {
        return $this->hasCorrectName($phpCsFile, 'EntityManager') &&
        $this->extendsAbstract($phpCsFile, $stackPointer, 'AbstractEntityManager');
    }
}
