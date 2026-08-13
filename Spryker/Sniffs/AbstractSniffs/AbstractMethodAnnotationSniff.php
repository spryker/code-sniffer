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
    protected const string LAYER_PERSISTENCE = 'Persistence';

    protected const string LAYER_COMMUNICATION = 'Communication';

    protected const string LAYER_BUSINESS = 'Business';

    /**
     * @var string
     */
    public $namespaces = 'Pyz,SprykerEco,SprykerMiddleware,SprykerSdk,Spryker,SprykerFeature';

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

    abstract protected function getMethodName(): string;

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

    protected function hasCorrectMethodAnnotation(File $phpCsFile, int $stackPointer, string $namespace): bool
    {
        return $this->hasMethodAnnotation($phpCsFile, $stackPointer, $namespace, true);
    }

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

    abstract protected function getSnifferIsApplicable(File $phpCsFile, int $stackPointer): bool;

    abstract protected function getMethodAnnotationFileName(File $phpCsFile, string $namespacePart): string;

    protected function hasDocBlock(File $phpCsFile, int $stackPointer): bool
    {
        return DocCommentHelper::hasDocComment($phpCsFile, $stackPointer);
    }

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

    protected function toDashedCase(string $input): string
    {
        return strtolower((string)preg_replace('/[A-Z]/', '-\\0', lcfirst($input)));
    }

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
