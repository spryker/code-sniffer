<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use Exception;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Traits\UseStatementsTrait;

/**
 * Ensures Doc Blocks for throws annotations are correct.
 * We only ever declare them for the exceptions inside the own method.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockThrowsSniff extends AbstractSprykerSniff
{
    use UseStatementsTrait;

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
        $tokens = $phpCsFile->getTokens();

        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);

        // We skip if no doc block found yet
        if (!$docBlockEndIndex) {
            return;
        }

        if ($phpCsFile->getDeclarationName($stackPointer) === null) {
            return;
        }

        // We skip for interface methods
        if (empty($tokens[$stackPointer]['scope_opener']) || empty($tokens[$stackPointer]['scope_closer'])) {
            return;
        }

        $exceptions = $this->extractExceptions($phpCsFile, $stackPointer);

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        // We skip for Spryker @api containing methods
        if ($this->isApiMethod($phpCsFile, $docBlockStartIndex)) {
            return;
        }

        $annotations = $this->extractExceptionAnnotations($phpCsFile, $docBlockStartIndex);

        $containsComplexThrowToken = $this->containsComplexThrowToken($tokens, $tokens[$stackPointer]['scope_opener'], $tokens[$stackPointer]['scope_closer']);

        if ($containsComplexThrowToken) {
            if ($annotations || !$this->containsThrowToken($tokens, $tokens[$stackPointer]['scope_opener'], $tokens[$stackPointer]['scope_closer'])) {
                return;
            }

            $phpCsFile->addError('Throw token found, but no annotation for it.', $docBlockEndIndex, 'ThrowAnnotationMissing');

            return;
        }

        $this->compareExceptionsAndAnnotations($phpCsFile, $exceptions, $annotations, $docBlockEndIndex);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return array
     */
    protected function extractExceptions(File $phpCsFile, int $stackPointer): array
    {
        $tokens = $phpCsFile->getTokens();

        $exceptions = [];

        $scopeOpener = $tokens[$stackPointer]['scope_opener'];
        $scopeCloser = $tokens[$stackPointer]['scope_closer'];

        for ($i = $scopeOpener; $i < $scopeCloser; $i++) {
            // We don't want to detect throws from nested scopes, so we'll just
            // skip those.
            if (in_array($tokens[$i]['code'], [T_FN, T_CLOSURE])) {
                $i = $tokens[$i]['scope_closer'];

                continue;
            }

            if ($tokens[$i]['code'] !== T_THROW) {
                continue;
            }

            $newIndex = $phpCsFile->findNext(T_NEW, $i + 1, $scopeCloser);

            $classIndex = $phpCsFile->findNext(T_STRING, $i + 1, $scopeCloser);
            $doubleColonIndex = $phpCsFile->findNext(T_DOUBLE_COLON, $i + 1, $scopeCloser);

            if (!$newIndex && !$classIndex && !$doubleColonIndex) {
                continue;
            }

            if ($newIndex) {
                $contentIndex = $phpCsFile->findNext(Tokens::$emptyTokens, $newIndex + 1, $scopeCloser, true);
                if (!$contentIndex) {
                    continue;
                }

                $exceptions[] = $this->extractException($phpCsFile, $contentIndex);

                continue;
            }

            if ($classIndex && $doubleColonIndex) {
                $exceptions[] = [
                    'start' => $classIndex,
                    'end' => $classIndex + 1,
                    'fullClass' => $tokens[$classIndex]['content'],
                    'class' => $tokens[$classIndex]['content'],
                ];

                continue;
            }
        }

        return $exceptions;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockStartIndex
     *
     * @return array
     */
    protected function extractExceptionAnnotations(File $phpCsFile, int $docBlockStartIndex): array
    {
        $tokens = $phpCsFile->getTokens();

        $throwTags = [];
        foreach ($tokens[$docBlockStartIndex]['comment_tags'] as $index) {
            if ($tokens[$index]['content'] !== '@throws') {
                continue;
            }

            if ($tokens[($index + 2)]['code'] !== T_DOC_COMMENT_STRING) {
                $throwTags[] = [
                    'index' => $index,
                    'fullClass' => null,
                    'class' => null,
                ];

                continue;
            }

            $fullClass = $tokens[($index + 2)]['content'];
            $space = strpos($fullClass, ' ');
            if ($space !== false) {
                $fullClass = substr($fullClass, 0, $space);
            }

            $class = $fullClass;
            $lastSeparator = strrpos($class, '\\');
            if ($lastSeparator !== false) {
                $class = substr($class, $lastSeparator + 1);
            }

            $throwTags[] = [
                'index' => $index,
                'fullClass' => $fullClass,
                'class' => $class,
            ];
        }

        return $throwTags;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $contentIndex
     *
     * @return array
     */
    protected function extractException(File $phpCsFile, int $contentIndex): array
    {
        $tokens = $phpCsFile->getTokens();

        $fullClass = '';

        $position = $contentIndex;
        while ($this->isGivenKind([T_NS_SEPARATOR, T_STRING], $tokens[$position])) {
            $fullClass .= $tokens[$position]['content'];
            ++$position;
        }

        $class = $fullClass;
        $lastSeparator = strrpos($class, '\\');
        if ($lastSeparator !== false) {
            $class = substr($class, $lastSeparator + 1);
        }

        return [
            'start' => $contentIndex,
            'end' => $position - 1,
            'fullClass' => $fullClass,
            'class' => $class,
        ];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param array $exceptions
     * @param array $annotations
     * @param int $docBlockEndIndex
     *
     * @return void
     */
    protected function compareExceptionsAndAnnotations(
        File $phpCsFile,
        array $exceptions,
        array $annotations,
        int $docBlockEndIndex
    ): void {
        $useStatements = $this->getUseStatements($phpCsFile);

        foreach ($annotations as $annotation) {
            if ($this->isInCode($annotation, $exceptions, $useStatements)) {
                continue;
            }

            $error = '@throw annotation `' . $annotation['fullClass'] . '` superfluous and needs to be removed';
            $fix = $phpCsFile->addFixableError($error, $annotation['index'], 'ThrowSuperfluous');
            if (!$fix) {
                continue;
            }

            $phpCsFile->fixer->beginChangeset();

            $this->removeLine($phpCsFile, $annotation['index']);

            $phpCsFile->fixer->endChangeset();
        }

        foreach ($exceptions as $exception) {
            $exception = $this->normalizeClassName($exception, $useStatements);
            if (empty($exception['fullClass'])) {
                // We skip for complex scenarios
                $phpCsFile->addError('Doc Block @throw annotation missing', $docBlockEndIndex, 'ThrowMissingManual');

                continue;
            }

            if ($this->isInAnnotation($exception, $annotations)) {
                continue;
            }

            $error = 'Doc Block @throw annotation `' . $exception['fullClass'] . '` missing';
            $fix = $phpCsFile->addFixableError($error, $docBlockEndIndex, 'ThrowMissing');
            if (!$fix) {
                continue;
            }

            $phpCsFile->fixer->beginChangeset();

            $this->addAnnotationLine($phpCsFile, $exception, $docBlockEndIndex);

            $phpCsFile->fixer->endChangeset();
        }
    }

    /**
     * @param array $exception
     * @param array $useStatements
     *
     * @return array Exception
     */
    protected function normalizeClassName(array $exception, array $useStatements): array
    {
        foreach ($useStatements as $useStatement) {
            if ($useStatement['alias'] === $exception['class']) {
                $exception['class'] = $useStatement['shortName'];
                $exception['fullClass'] = $useStatement['fullName'];
            }
        }

        return $exception;
    }

    /**
     * @param array $annotation
     * @param array $exceptions
     * @param array $useStatements
     *
     * @return bool
     */
    protected function isInCode(array $annotation, array $exceptions, array $useStatements): bool
    {
        foreach ($exceptions as $exception) {
            $exception = $this->normalizeClassName($exception, $useStatements);

            if ($annotation['class'] === $exception['class']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $exception
     * @param array $annotations
     *
     * @return bool
     */
    protected function isInAnnotation(array $exception, array $annotations): bool
    {
        foreach ($annotations as $annotation) {
            if ($exception['class'] === $annotation['class']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $position
     *
     * @return void
     */
    protected function removeLine(File $phpCsFile, int $position): void
    {
        $tokens = $phpCsFile->getTokens();

        $index = $this->getFirstTokenOfLine($tokens, $position);
        while ($tokens[$index]['line'] === $tokens[$position]['line']) {
            $phpCsFile->fixer->replaceToken($index, '');
            $index++;
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param array $exception
     * @param int $docBlockEndIndex
     *
     * @throws \Exception
     *
     * @return void
     */
    protected function addAnnotationLine(File $phpCsFile, array $exception, int $docBlockEndIndex): void
    {
        $tokens = $phpCsFile->getTokens();

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        $throwAnnotationIndex = $this->getThrowAnnotationIndex($tokens, $docBlockStartIndex);
        if (!$throwAnnotationIndex) {
            throw new Exception('Should not happen');
        }

        $phpCsFile->fixer->beginChangeset();

        $phpCsFile->fixer->addNewlineBefore($throwAnnotationIndex);
        $phpCsFile->fixer->addContentBefore($throwAnnotationIndex, '     * @throws ' . $exception['fullClass']);

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param array $tokens
     * @param int $docBlockStartIndex
     *
     * @return int
     */
    protected function getThrowAnnotationIndex(array $tokens, int $docBlockStartIndex): int
    {
        foreach ($tokens[$docBlockStartIndex]['comment_tags'] as $index) {
            if ($tokens[$index]['content'] !== '@throws') {
                continue;
            }

            $throwAnnotationIndex = $index;
            while ($tokens[$throwAnnotationIndex + 1]['line'] === $tokens[$index]['line']) {
                $throwAnnotationIndex++;
            }
            $throwAnnotationIndex++;

            return $throwAnnotationIndex;
        }

        foreach ($tokens[$docBlockStartIndex]['comment_tags'] as $index) {
            if ($tokens[$index]['content'] !== '@return') {
                continue;
            }

            $throwAnnotationIndex = $this->getFirstTokenOfLine($tokens, $index);

            return $throwAnnotationIndex;
        }

        $throwAnnotationIndex = $this->getFirstTokenOfLine($tokens, $tokens[$docBlockStartIndex]['comment_closer']);

        return $throwAnnotationIndex;
    }

    /**
     * @param array $tokens
     * @param int $scopeOpener
     * @param int $scopeCloser
     *
     * @return bool
     */
    protected function containsComplexThrowToken(array $tokens, int $scopeOpener, int $scopeCloser): bool
    {
        for ($i = $scopeOpener + 1; $i < $scopeCloser; $i++) {
            if ($tokens[$i]['code'] !== T_THROW) {
                continue;
            }

            if ($tokens[$i + 2]['code'] !== T_VARIABLE) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param array $tokens
     * @param int $scopeOpener
     * @param int $scopeCloser
     *
     * @return bool
     */
    protected function containsThrowToken(array $tokens, int $scopeOpener, int $scopeCloser): bool
    {
        for ($i = $scopeOpener + 1; $i < $scopeCloser; $i++) {
            if ($tokens[$i]['code'] !== T_THROW) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockStartIndex
     *
     * @return bool
     */
    protected function isApiMethod(File $phpCsFile, int $docBlockStartIndex): bool
    {
        $tokens = $phpCsFile->getTokens();

        foreach ($tokens[$docBlockStartIndex]['comment_tags'] as $index) {
            if ($tokens[$index]['content'] === '@api') {
                return true;
            }
        }

        return false;
    }
}
