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
     * What is the root directoy to look into? Defaults to `src/`
     *
     * @var string
     */
    public $rootDir = 'src';

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

        $filename = $fullFilename = $phpcsFile->getFilename();
        if ($this->isRoot) {
            $filename = $this->normalizeFilename($fullFilename);
        }

        $namespace = $namespaceStatement['namespace'];
        $pathToNamespace = $this->extractNamespaceFromPath($filename);

        if ($pathToNamespace === null) {
            return;
        }

        if ($namespace === $pathToNamespace) {
            return;
        }

        $error = sprintf('Namespace `%s` does not fit to folder structure `%s`', $namespace, $pathToNamespace);
        $phpcsFile->addError($error, $namespaceStatement['start'], 'NamespaceFolderMismatch');
    }

    /**
     * Extracts the expected namespace from the file path.
     *
     * Supports multiple folder structures:
     * - src/Namespace/Module/src/Namespace/Layer/Module/File.php
     * - src/Namespace/Module/tests/NamespaceTest/Layer/Module/File.php
     * - src/Namespace/File.php (standard PSR-4)
     *
     * @param string $filename
     *
     * @return string|null
     */
    protected function extractNamespaceFromPath(string $filename): ?string
    {
        $start = '/';
        if ($this->isRoot) {
            $fullFilename = $filename;
            $filename = $this->normalizeFilename($fullFilename);
            if ($fullFilename !== $filename) {
                $start = '^';
            }
        }

        // Try monorepo structure: src/Vendor/Module/(src|tests)/Vendor/...
        $monorepoPattern = '#' . $start . $this->rootDir . '/([^/]+)/([^/]+)/(src|tests)/(.+)#';
        if (preg_match($monorepoPattern, $filename, $matches)) {
            $extractedPath = $matches[4];
            $pathWithoutFilename = substr($extractedPath, 0, strrpos($extractedPath, '/') ?: 0);

            // Remove special directories like _support, _helpers, etc. from the namespace path
            $pathWithoutFilename = $this->removeSpecialDirectories($pathWithoutFilename);

            return str_replace('/', '\\', $pathWithoutFilename);
        }

        // Try standard structure
        $pattern = '#' . $start . $this->rootDir . '/(' . $this->namespace . ')/(.+)#';
        if ($this->isRoot) {
            $pattern = '#' . $start . $this->rootDir . '/(.+)#';
        }

        if (!preg_match($pattern, $filename, $matches)) {
            return null;
        }

        if ($this->isRoot) {
            $extractedPath = $this->namespace . '/' . $matches[1];
        } else {
            $extractedPath = $matches[1] . '/' . $matches[2];
        }
        $pathWithoutFilename = substr($extractedPath, 0, strrpos($extractedPath, '/') ?: 0);

        return str_replace('/', '\\', $pathWithoutFilename);
    }

    /**
     * Removes special directories (like _support, _helpers) from the path that should not be part of the namespace.
     *
     * @param string $path
     *
     * @return string
     */
    protected function removeSpecialDirectories(string $path): string
    {
        $segments = explode('/', $path);
        $filteredSegments = array_filter($segments, function ($segment) {
            return (bool)$segment && $segment[0] !== '_';
        });

        return implode('/', $filteredSegments);
    }

    /**
     * Removes the current working directory (root) if possible.
     *
     * @param string $getFilename
     *
     * @return string
     */
    protected function normalizeFilename(string $getFilename): string
    {
        return str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $getFilename);
    }
}
