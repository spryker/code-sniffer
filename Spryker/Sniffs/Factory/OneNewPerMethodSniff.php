<?php

namespace Spryker\Sniffs\Factory;

use PHP_CodeSniffer_File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Spryker Factory classes may not contain multiple object instantiations.
 *
 * Note: This sniff will only run on Spryker Core files.
 */
class OneNewPerMethodSniff extends AbstractSprykerSniff
{

    /**
     * @inheritdoc
     */
    public function register()
    {
        return [
            T_FUNCTION,
        ];
    }

    /**
     * @inheritdoc
     */
    public function process(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        if ($this->isFactory($phpCsFile) && $this->isSprykerClass($phpCsFile) && $this->hasMoreThenOneNewInMethod($phpCsFile, $stackPointer)) {
            $classMethod = $this->getClassMethod($phpCsFile, $stackPointer);
            $phpCsFile->addError(
                $classMethod . ' contains more then one new. Fix this by extract a method.',
                $stackPointer
            );
        }
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     *
     * @return bool
     */
    protected function isSprykerClass(PHP_CodeSniffer_File $phpCsFile)
    {
        $namespace = $this->getNamespace($phpCsFile);

        return ($namespace === static::NAMESPACE_SPRYKER);
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     *
     * @return string
     */
    protected function getClassName(PHP_CodeSniffer_File $phpCsFile)
    {
        $fileName = $phpCsFile->getFilename();
        $fileNameParts = explode(DIRECTORY_SEPARATOR, $fileName);
        $sourceDirectoryPosition = array_search('src', array_values($fileNameParts));
        $classNameParts = array_slice($fileNameParts, $sourceDirectoryPosition + 1);
        $className = implode('\\', $classNameParts);
        $className = str_replace('.php', '', $className);

        return $className;
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return string
     */
    protected function getMethodName(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $tokens = $phpCsFile->getTokens();
        $methodNamePosition = $phpCsFile->findNext(T_STRING, $stackPointer);
        $methodName = $tokens[$methodNamePosition]['content'];

        return $methodName;
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     *
     * @return bool
     */
    protected function isFactory(PHP_CodeSniffer_File $phpCsFile)
    {
        $className = $this->getClassName($phpCsFile);

        return (substr($className, -7) === 'Factory');
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasMoreThenOneNewInMethod(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $openPointer = $phpCsFile->findNext(T_OPEN_CURLY_BRACKET, $stackPointer);
        $closePointer = $phpCsFile->findNext(T_CLOSE_CURLY_BRACKET, $openPointer);

        $firstNewPosition = $phpCsFile->findNext(T_NEW, $openPointer, $closePointer);
        if ($firstNewPosition === false) {
            return false;
        }

        $secondNewPosition = $phpCsFile->findNext(T_NEW, $firstNewPosition + 1, $closePointer);

        return ($secondNewPosition !== false);
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPointer
     *
     * @return string
     */
    protected function getClassMethod(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
    {
        $className = $this->getClassName($phpCsFile);
        $methodName = $this->getMethodName($phpCsFile, $stackPointer);

        $classMethod = $className . '::' . $methodName;

        return $classMethod;
    }

}
