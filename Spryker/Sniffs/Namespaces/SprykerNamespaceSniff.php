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
     * For non root case: Regular expressions allowed, e.g. `Foo|Bar*`
     *
     * @var string
     */
    public $namespace = 'Spryker.*';

    /**
     * Use this to make the namespace a root namespace, as for most modern PSR-4 packages.
     *
     * @var bool
     */
    public $isRoot = false;

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
    public function process(File $phpcsFile, $stackPtr): void
    {
        $namespaceStatement = $this->getNamespaceStatement($phpcsFile);
        if (!$namespaceStatement) {
            return;
        }

        $filename = $phpcsFile->getFilename();

        $pattern = '#/src/(' . $this->namespace . ')/(.+)#';
        if ($this->isRoot) {
            $pattern = '#/src/(.+)#';
        }

        preg_match($pattern, $filename, $matches);
        if (!$matches) {
            return;
        }

        if ($this->isRoot) {
            $extractedPath = $this->namespace . '/' . $matches[1];
        } else {
            $extractedPath = $matches[1] . '/' . $matches[2];
        }
        $pathWithoutFilename = substr($extractedPath, 0, strrpos($extractedPath, DIRECTORY_SEPARATOR) ?: 0);

        $namespace = $namespaceStatement['namespace'];
        $pathToNamespace = str_replace(DIRECTORY_SEPARATOR, '\\', $pathWithoutFilename);
        if ($namespace === $pathToNamespace) {
            return;
        }

        $error = sprintf('Namespace `%s` does not fit to folder structure `%s`', $namespace, $pathToNamespace);
        $phpcsFile->addError($error, $namespaceStatement['start'], 'NamespaceFolderMismatch');
    }
}
