<?php

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Sniff;

/**
 * Checks if methods of Spryker facade API classes have a matching interface method.
 */
class SprykerFacadeSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * @return array
     */
    public function register()
    {
        return [
            T_CLASS, T_INTERFACE,
        ];
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
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
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function checkFacade(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $name = $this->findClassOrInterfaceName($phpCsFile, $stackPointer);
        $facadeInterfaceFile = str_replace('Facade.php', 'FacadeInterface.php', $phpCsFile->getFilename());

        if (!file_exists($facadeInterfaceFile)) {
            $phpCsFile->addError('FacadeInterface missing for ' . $name, $stackPointer);
        }
    }

    /**
     * Facade interfaces must have a "Specification" block as part of the contract.
     *
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function checkInterface(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
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

        if (array_values($interfaceMethods) !== array_values($methods)) {
            $missingMethods = array_diff($methods, $interfaceMethods);

            $phpCsFile->addError(
                sprintf('Interface methods do not match facade methods: "%s" missing', implode(', ', $missingMethods)),
                $stackPointer
            );
        }
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isSprykerFacadeApiClass(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
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
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasNamespace(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $namespacePosition = $phpCsFile->findPrevious(T_NAMESPACE, $stackPointer);
        if (!$namespacePosition) {
            return false;
        }

        return true;
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return string
     */
    protected function getNamespace(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
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
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return string
     */
    protected function findClassOrInterfaceName(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $classOrInterfaceNamePosition = $phpCsFile->findNext(T_STRING, $stackPointer);

        return $phpCsFile->getTokens()[$classOrInterfaceNamePosition]['content'];
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isFacadeInterface($phpCsFile, $stackPointer)
    {
        $namespace = $this->getNamespace($phpCsFile, $stackPointer);
        $name = $this->findClassOrInterfaceName($phpCsFile, $stackPointer);

        if (preg_match('/^Spryker\\\\Zed\\\\(.+?)\\\\Business$/', $namespace) && preg_match('/^(.+?)(FacadeInterface)$/', $name)) {
            return true;
        }

        return false;
    }

}
