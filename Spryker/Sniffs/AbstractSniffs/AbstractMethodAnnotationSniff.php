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
    protected const LAYER_PERSISTENCE = 'Persistence';

    protected const LAYER_COMMUNICATION = 'Communication';

    protected const LAYER_BUSINESS = 'Business';

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
        if (
            !$this->hasMethodAnnotation($phpCsFile, $stackPointer)
            && $this->fileExists($phpCsFile, $this->getMethodAnnotationFileName($phpCsFile))
        ) {
            $fix = $phpCsFile->addFixableError($this->getMethodName() . '() annotation missing', $stackPointer, 'Missing');

            if ($fix) {
                $this->addMethodAnnotation($phpCsFile, $stackPointer);
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function hasMethodAnnotation(File $phpCsFile, int $stackPointer): bool
    {
        $position = $phpCsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPointer);
        $tokens = $phpCsFile->getTokens();

        while ($position !== false) {
            $position = $phpCsFile->findPrevious(T_DOC_COMMENT_TAG, $position);

            if ($position !== false) {
                if (strpos($tokens[$position + 2]['content'], $this->getMethodName() . '()') !== false) {
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
     *
     * @return void
     */
    protected function addMethodAnnotation(File $phpCsFile, int $stackPointer): void
    {
        $phpCsFile->fixer->beginChangeset();

        $stackPointer = $this->getStackPointerOfClassBegin($phpCsFile, $stackPointer);

        if (!$this->hasDocBlock($phpCsFile, $stackPointer)) {
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, ' */');
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore(
                $stackPointer,
                ' * @method ' . $this->getMethodAnnotationFileName($phpCsFile) . ' ' . $this->getMethodName() . '()'
            );
            $phpCsFile->fixer->addNewlineBefore($stackPointer);
            $phpCsFile->fixer->addContentBefore($stackPointer, '/**');
        } else {
            $position = $phpCsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $stackPointer);
            $phpCsFile->fixer->addNewlineBefore($position);
            $phpCsFile->fixer->addContentBefore(
                $position,
                ' * @method ' . $this->getMethodAnnotationFileName($phpCsFile) . ' ' . $this->getMethodName() . '()'
            );
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
     *
     * @return string
     */
    abstract protected function getMethodAnnotationFileName(File $phpCsFile): string;

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
     *
     * @return bool
     */
    protected function fileExists(File $phpCsFile, string $className): bool
    {
        $fileName = $phpCsFile->getFilename();
        $fileNameParts = explode(DIRECTORY_SEPARATOR, $fileName);
        $sourceDirectoryPosition = array_search('src', $fileNameParts, true);
        $basePathParts = array_slice($fileNameParts, 0, $sourceDirectoryPosition + 1);

        $basePath = implode(DIRECTORY_SEPARATOR, $basePathParts) . DIRECTORY_SEPARATOR;
        $classFileName = str_replace('\\', DIRECTORY_SEPARATOR, $className);

        $fileName = $basePath . $classFileName . '.php';

        return file_exists($fileName);
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

        return $stackPointer;
    }
}
