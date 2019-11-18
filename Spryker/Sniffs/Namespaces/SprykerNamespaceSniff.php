<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use Spryker\Traits\BasicsTrait;

/**
 * Makes sure the namespace declared in each class file fits to the folder structure.
 */
class SprykerNamespaceSniff implements Sniff
{
    use BasicsTrait;

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_CLASS, T_INTERFACE];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $namespaceStatement = $this->getNamespaceStatement($phpcsFile);
        if (!$namespaceStatement) {
            return;
        }

        $filename = $phpcsFile->getFilename();

        preg_match('#/src/(Spryker.*)/(.+)#', $filename, $matches);
        if (!$matches) {
            return;
        }

        $extractedPath = $matches[1] . '/' . $matches[2];
        $pathWithoutFilename = substr($extractedPath, 0, strrpos($extractedPath, DIRECTORY_SEPARATOR));

        $namespace = $namespaceStatement['namespace'];
        $pathToNamespace = str_replace(DIRECTORY_SEPARATOR, '\\', $pathWithoutFilename);
        if ($namespace === $pathToNamespace) {
            return;
        }

        $error = sprintf('Namespace `%s` does not fit to folder structure `%s`', $namespace, $pathToNamespace);
        $phpcsFile->addError($error, $namespaceStatement['start'], 'NamespaceFolderMismatch');
    }
}
