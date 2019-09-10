<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Checks if methods of Spryker facade API classes have a matching interface method.
 */
class SprykerFacadeSniff implements Sniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_CLASS, T_INTERFACE,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        if (!$this->isSprykerFacadeApiClass($phpCsFile, $stackPointer)) {
            return;
        }

        if ($this->isFacadeInterface($phpCsFile, $stackPointer)) {
            $this->checkInterface($phpCsFile, $stackPointer);

            return;
        }

        $this->checkFacade($phpCsFile, $stackPointer);
    }

    /**
     * Facades must have a matching interface.
     *
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function checkFacade(File $phpCsFile, int $stackPointer): void
    {
        $name = $this->findClassOrInterfaceName($phpCsFile, $stackPointer);
        $facadeInterfaceFile = str_replace('Facade.php', 'FacadeInterface.php', $phpCsFile->getFilename());

        if (!file_exists($facadeInterfaceFile)) {
            $phpCsFile->addError('FacadeInterface missing for ' . $name, $stackPointer, 'InterfaceMissing');
        }
    }

    /**
     * Facade methods need to appear in its interface (and vice versa)
     *
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function checkInterface(File $phpCsFile, int $stackPointer): void
    {
        $facadeFile = str_replace('FacadeInterface.php', 'Facade.php', $phpCsFile->getFilename());

        $content = file_get_contents($facadeFile);
        preg_match_all('/public function (\w+)\b/', $content, $matches);
        $methods = $matches[1];
        asort($methods);

        $interfaceContent = file_get_contents($phpCsFile->getFilename());
        preg_match_all('/public function (\w+)\b/', $interfaceContent, $matches);
        $interfaceMethods = $matches[1];
        asort($interfaceMethods);

        if (array_values($interfaceMethods) === array_values($methods)) {
            return;
        }

        $missingInterfaceMethods = array_diff($methods, $interfaceMethods);
        $missingInterfaceImplementations = array_diff($interfaceMethods, $methods);

        if (count($missingInterfaceMethods) > 0) {
            $phpCsFile->addError(
                sprintf('Interface methods do not match facade methods: "%s" missing', implode(', ', $missingInterfaceMethods)),
                $stackPointer,
                'InterfaceMethodMissing'
            );
        }

        if (count($missingInterfaceImplementations) > 0) {
            $phpCsFile->addError(
                sprintf('Interface method has no implementation: "%s" missing', implode(', ', $missingInterfaceImplementations)),
                $stackPointer,
                'InterfaceImplementationMissing'
            );
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isSprykerFacadeApiClass(File $phpCsFile, int $stackPointer): bool
    {
        if (!$this->hasNamespace($phpCsFile, $stackPointer)) {
            return false;
        }

        $namespace = $this->getNamespace($phpCsFile, $stackPointer);
        $name = $this->findClassOrInterfaceName($phpCsFile, $stackPointer);
        if (!$name || $name === 'AbstractFacade') {
            return false;
        }

        if (preg_match('/^Spryker\\\\Zed\\\\(.*?)\\\\Business$/', $namespace) && preg_match('/^(.+?)(Facade|FacadeInterface)$/', $name)) {
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
    protected function hasNamespace(File $phpCsFile, int $stackPointer): bool
    {
        $namespacePosition = $phpCsFile->findPrevious(T_NAMESPACE, $stackPointer);
        if (!$namespacePosition) {
            return false;
        }

        return true;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return string
     */
    protected function getNamespace(File $phpCsFile, int $stackPointer): string
    {
        $namespacePosition = $phpCsFile->findPrevious(T_NAMESPACE, $stackPointer);
        $endOfNamespacePosition = $phpCsFile->findEndOfStatement($namespacePosition);

        $tokens = $phpCsFile->getTokens();
        $namespaceTokens = array_splice($tokens, $namespacePosition + 2, $endOfNamespacePosition - $namespacePosition - 2);

        $namespace = '';
        foreach ($namespaceTokens as $token) {
            $namespace .= $token['content'];
        }

        return $namespace;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return string
     */
    protected function findClassOrInterfaceName(File $phpCsFile, int $stackPointer): string
    {
        $classOrInterfaceNamePosition = $phpCsFile->findNext(T_STRING, $stackPointer);

        return $phpCsFile->getTokens()[$classOrInterfaceNamePosition]['content'];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isFacadeInterface(File $phpCsFile, int $stackPointer): bool
    {
        $namespace = $this->getNamespace($phpCsFile, $stackPointer);
        $name = $this->findClassOrInterfaceName($phpCsFile, $stackPointer);

        if (preg_match('/^Spryker\\\\Zed\\\\(.+?)\\\\Business$/', $namespace) && preg_match('/^(.+?)(FacadeInterface)$/', $name)) {
            return true;
        }

        return false;
    }
}
