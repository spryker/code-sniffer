<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Factory;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Spryker Factory classes may not contain multiple object instantiations.
 *
 * Note: This sniff will only run on Spryker Core files.
 */
class OneNewPerMethodSniff extends AbstractSprykerSniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_FUNCTION,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        if ($this->isFactory($phpCsFile) && $this->isSprykerNamespace($phpCsFile) && $this->hasMoreThenOneNewInMethod($phpCsFile, $stackPointer)) {
            $classMethod = $this->getClassMethod($phpCsFile, $stackPointer);
            $phpCsFile->addError(
                $classMethod . ' contains more then one new. Fix this by extract a method.',
                $stackPointer,
                'OnlyOneNewAllowed'
            );
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getClassName(File $phpCsFile): string
    {
        $fileName = $phpCsFile->getFilename();
        $fileNameParts = explode(DIRECTORY_SEPARATOR, $fileName);
        $sourceDirectoryPosition = array_search('src', array_values($fileNameParts), true);
        $classNameParts = array_slice($fileNameParts, $sourceDirectoryPosition + 1);
        $className = implode('\\', $classNameParts);
        $className = str_replace('.php', '', $className);

        return $className;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return string
     */
    protected function getMethodName(File $phpCsFile, int $stackPointer): string
    {
        $tokens = $phpCsFile->getTokens();
        $methodNamePosition = $phpCsFile->findNext(T_STRING, $stackPointer);
        $methodName = $tokens[$methodNamePosition]['content'];

        return $methodName;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isFactory(File $phpCsFile): bool
    {
        $className = $this->getClassName($phpCsFile);

        return (substr($className, -7) === 'Factory');
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasMoreThenOneNewInMethod(File $phpCsFile, int $stackPointer): bool
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
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return string
     */
    protected function getClassMethod(File $phpCsFile, int $stackPointer): string
    {
        $className = $this->getClassName($phpCsFile);
        $methodName = $this->getMethodName($phpCsFile, $stackPointer);

        $classMethod = $className . '::' . $methodName;

        return $classMethod;
    }
}
