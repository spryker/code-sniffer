<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\AbstractSniffs;

use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Helpers\DocCommentHelper;

abstract class AbstractMethodAnnotationSniff extends AbstractClassDetectionSprykerSniff
{
    /**
     * @var string
     */
    protected const LAYER_PERSISTENCE = 'Persistence';

    /**
     * @var string
     */
    protected const LAYER_COMMUNICATION = 'Communication';

    /**
     * @var string
     */
    protected const LAYER_BUSINESS = 'Business';

    /**
     * @var string
     */
    public $namespaces = 'Pyz,SprykerEco,SprykerMiddleware,SprykerSdk,Spryker';

    /**
     * @var bool
     */
    protected $fileExists = false;

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_CLASS,
        ];
    }

    /**
     * @return string
     */
    abstract protected function getMethodName(): string;

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    abstract protected function getMethodFileAddedName(File $phpCsFile): string;

    /**
     * @inheritDoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        if (!$this->getSnifferIsApplicable($phpCsFile, $stackPointer)) {
            return;
        }

        $this->runSniffer($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function runSniffer(File $phpCsFile, int $stackPointer): void
    {
        $foundInNamespace = $this->getNamespaceForFilename($phpCsFile);
        $changeMethodAnnotation = false;
        if (!$foundInNamespace) {
            return;
        }
        if ($this->hasCorrectMethodAnnotation($phpCsFile, $stackPointer, $foundInNamespace)) {
            return;
        }
        if ($this->hasMethodAnnotation($phpCsFile, $stackPointer, $foundInNamespace)) {
            $changeMethodAnnotation = true;
        }
        $errorType = $changeMethodAnnotation ? 'wrong' : 'missing';
        $error = sprintf(
            '%s() annotation is %s (found in "%s" namespace)',
            $this->getMethodName(),
            $errorType,
            $foundInNamespace,
        );
        $fix = $phpCsFile->addFixableError($error, $stackPointer, ucfirst($errorType));

        if (!$fix) {
            return;
        }
        if ($errorType === 'missing') {
            $this->addMethodAnnotation($phpCsFile, $stackPointer, $foundInNamespace);
        } else {
            $this->changeMethodAnnotation($phpCsFile, $stackPointer, $foundInNamespace);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string|null
     */
    protected function getNamespaceForFilename(File $phpCsFile): ?string
    {
        $namespaces = explode(',', $this->namespaces);
        foreach ($namespaces as $namespace) {
            if (
                $this->fileExists(
                    $phpCsFile,
                    $this->getMethodAnnotationFileName($phpCsFile, $namespace),
                    $namespace,
                )
            ) {
                return $namespace;
            }
        }

        return null;
    }

    /**
     * Checks if the '@method' annotation for the specific method already exists
     * in the class. When $strictCheck is set to true, the method also checks
     * whether the referenced namespace and the class name are as expected.
     *
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     * @param string $namespace
     * @param bool $strictCheck
     *
     * @return bool
     */
    protected function hasMethodAnnotation(File $phpCsFile, int $stackPointer, string $namespace, bool $strictCheck = false): bool
    {
        $position = $phpCsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPointer);
        $tokens = $phpCsFile->getTokens();

        while ($position !== false) {
            $position = $phpCsFile->findPrevious(T_DOC_COMMENT_TAG, $position);

            if ($position !== false) {
                $methodAnnotation = $tokens[$position + 2]['content'];
                if (strpos($methodAnnotation, $this->getMethodName() . '()') !== false) {
                    if ($strictCheck) {
                        $expectedCommentPattern = sprintf(
                            '\\\%s\\\%s\\\%s %s()',
                            $namespace,
                            '.*',
                            $this->getMethodFileAddedName($phpCsFile),
                            $this->getMethodName(),
                        );

                        return (bool)preg_match("/$expectedCommentPattern/", $methodAnnotation);
                    }

                    return true;
                }

                $position--;
            }
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     * @param string $namespace
     *
     * @return bool
     */
    protected function hasCorrectMethodAnnotation(File $phpCsFile, int $stackPointer, string $namespace): bool
    {
        return $this->hasMethodAnnotation($phpCsFile, $stackPointer, $namespace, true);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     * @param string $namespacePart
     *
     * @return void
     */
    protected function addMethodAnnotation(File $phpCsFile, int $stackPointer, string $namespacePart): void
    {
        $phpCsFile->fixer->beginChangeset();

        $stackPointer = $this->getStackPointerOfClassBegin($phpCsFile, $stackPointer);

        if (!$this->hasDocBlock($phpCsFile, $stackPointer)) {
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, ' */');
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore(
                $stackPointer,
                ' * @method ' . $this->getMethodAnnotationFileName($phpCsFile, $namespacePart) . ' ' . $this->getMethodName() . '()',
            );
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, '/**');
        } else {
            $position = $phpCsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPointer);
            if ($position) {
                $phpCsFile->fixer->addNewlineBefore($position);
                $phpCsFile->fixer->addContentBefore(
                    $position,
                    ' * @method ' . $this->getMethodAnnotationFileName($phpCsFile, $namespacePart) . ' ' . $this->getMethodName() . '()',
                );
            }
        }

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     * @param string $namespacePart
     *
     * @return void
     */
    protected function changeMethodAnnotation(File $phpCsFile, int $stackPointer, string $namespacePart): void
    {
        $phpCsFile->fixer->beginChangeset();

        $stackPointer = (int)$this->getStackPointerOfClassBegin($phpCsFile, $stackPointer);
        $docBlockEndIndex = (int)$phpCsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPointer);
        $docBlockStartIndex = $phpCsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $docBlockEndIndex);
        $tokens = $phpCsFile->getTokens();
        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if (strpos($tokens[$i]['content'], $this->getMethodName()) === false) {
                continue;
            }
            $newContent = sprintf(
                '%s %s()',
                $this->getMethodAnnotationFileName($phpCsFile, $namespacePart),
                $this->getMethodName(),
            );
            $phpCsFile->fixer->replaceToken($i, $newContent);
        }
        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    abstract protected function getSnifferIsApplicable(File $phpCsFile, int $stackPointer): bool;

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param string $namespacePart
     *
     * @return string
     */
    abstract protected function getMethodAnnotationFileName(File $phpCsFile, string $namespacePart): string;

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasDocBlock(File $phpCsFile, int $stackPointer): bool
    {
        return DocCommentHelper::hasDocComment($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param string $className
     * @param string $namespacePart
     *
     * @return bool
     */
    protected function fileExists(File $phpCsFile, string $className, string $namespacePart): bool
    {
        $fileName = $phpCsFile->getFilename();
        $fileNameParts = explode(DIRECTORY_SEPARATOR, $fileName);
        $sourceDirectoryPosition = array_search('src', $fileNameParts, true);
        $basePathParts = array_slice($fileNameParts, 0, $sourceDirectoryPosition + 1);

        $basePath = implode(DIRECTORY_SEPARATOR, $basePathParts) . DIRECTORY_SEPARATOR;
        $classFileName = str_replace('\\', DIRECTORY_SEPARATOR, $className);

        $fileName = $basePath . $classFileName . '.php';
        $fileName = str_replace(
            DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR,
            $fileName,
        );

        $fileNameParts = explode(DIRECTORY_SEPARATOR, $fileName);
        $vendorPath = $this->getVendorPath($basePath, $namespacePart, $this->getModule($phpCsFile));

        $fileNameParts[$sourceDirectoryPosition] = $vendorPath;
        $vendorFileName = implode(DIRECTORY_SEPARATOR, $fileNameParts);

        return file_exists($fileName) || file_exists($vendorFileName);
    }

    /**
     * @param string $input
     *
     * @return string
     */
    protected function toDashedCase(string $input): string
    {
        return strtolower((string)preg_replace('/[A-Z]/', '-\\0', lcfirst($input)));
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return int
     */
    protected function getStackPointerOfClassBegin(File $phpCsFile, int $stackPointer): int
    {
        $abstractPosition = (int)$phpCsFile->findPrevious(T_ABSTRACT, $stackPointer);
        if ($abstractPosition) {
            return $abstractPosition;
        }
        $finalPosition = (int)$phpCsFile->findPrevious(T_FINAL, $stackPointer);
        if ($finalPosition) {
            return $finalPosition;
        }

        return $stackPointer;
    }

    /**
     * @param string $basePath
     * @param string $namespace
     * @param string $module
     *
     * @return string
     */
    protected function getVendorPath(string $basePath, string $namespace, string $module): string
    {
        $namespaceElement = $this->toDashedCase($namespace);
        $moduleElement = $this->toDashedCase($module);

        $rootPath = dirname($basePath);
        $path = $rootPath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'spryker' . DIRECTORY_SEPARATOR . $namespaceElement . DIRECTORY_SEPARATOR;

        if (in_array($namespaceElement, ['spryker', 'spryker-shop'], true) && is_dir($path)) {
            return 'vendor' . DIRECTORY_SEPARATOR
                . 'spryker' . DIRECTORY_SEPARATOR
                . $namespaceElement . DIRECTORY_SEPARATOR
                . 'Bundles' . DIRECTORY_SEPARATOR
                . $module . DIRECTORY_SEPARATOR
                . 'src';
        }

        return 'vendor' . DIRECTORY_SEPARATOR
            . $namespaceElement . DIRECTORY_SEPARATOR
            . $moduleElement . DIRECTORY_SEPARATOR
            . 'src';
    }
}
