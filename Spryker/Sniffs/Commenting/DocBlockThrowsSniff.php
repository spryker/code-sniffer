<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
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
    public function register()
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
     * @param int $docBlockEndIndex
     * @param int $docBlockStartIndex
     * @param string|null $defaultValueType
     *
     * @return void
     */
    protected function handleMissingVar(File $phpCsFile, $docBlockEndIndex, $docBlockStartIndex, $defaultValueType)
    {
        $error = 'Doc Block annotation @var for variable missing';
        if ($defaultValueType === null) {
            $phpCsFile->addError($error, $docBlockEndIndex, 'VarAnnotationMissing');
            return;
        }

        $error .= ', type `' . $defaultValueType . '` detected';
        $fix = $phpCsFile->addFixableError($error, $docBlockEndIndex, 'WrongType');
        if (!$fix) {
            return;
        }

        $index = $phpCsFile->findPrevious(Tokens::$emptyTokens, $docBlockEndIndex - 1, $docBlockStartIndex, true);
        if (!$index) {
            $index = $docBlockStartIndex;
        }

        $phpCsFile->fixer->beginChangeset();
        $phpCsFile->fixer->addNewline($index);
        $phpCsFile->fixer->addContent($index, "\t" . ' * @var ' . $defaultValueType);
        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return array
     */
    protected function extractExceptions($phpCsFile, $stackPointer)
    {
        $tokens = $phpCsFile->getTokens();

        $exceptions = [];

        $scopeOpener = $tokens[$stackPointer]['scope_opener'];
        $scopeCloser = $tokens[$stackPointer]['scope_closer'];

        for ($i = $scopeOpener; $i < $scopeCloser; $i++) {
            if ($tokens[$i]['code'] !== T_THROW) {
                continue;
            }

            $newIndex = $phpCsFile->findNext(T_NEW, $i + 1, $scopeCloser);
            if (!$newIndex) {
                continue;
            }

            $contentIndex = $phpCsFile->findNext(Tokens::$emptyTokens, $newIndex + 1, $scopeCloser, true);
            if (!$contentIndex) {
                continue;
            }

            $exceptions[] = $this->extractException($phpCsFile, $contentIndex);
        }

        return $exceptions;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockStartIndex
     *
     * @return array
     */
    protected function extractExceptionAnnotations(File $phpCsFile, $docBlockStartIndex)
    {
        $tokens = $phpCsFile->getTokens();

        $throwTags = [];
        foreach ($tokens[$docBlockStartIndex]['comment_tags'] as $index) {
            if ($tokens[$index]['content'] !== '@throws') {
                continue;
            }

            if ($tokens[($index + 2)]['code'] !== T_DOC_COMMENT_STRING) {
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
    protected function extractException(File $phpCsFile, $contentIndex)
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
    protected function compareExceptionsAndAnnotations(File $phpCsFile, array $exceptions, array $annotations, $docBlockEndIndex)
    {
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
                // We skip for complex scnarios
                if ($annotations) {
                    $phpCsFile->addError('Doc Block @throw annotation missing', $docBlockEndIndex, 'ThrowMissingManual');
                }
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
    protected function normalizeClassName(array $exception, array $useStatements)
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
    protected function isInCode(array $annotation, array $exceptions, array $useStatements)
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
    protected function isInAnnotation(array $exception, array $annotations)
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
    protected function removeLine(File $phpCsFile, $position)
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
    protected function addAnnotationLine(File $phpCsFile, array $exception, $docBlockEndIndex)
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
    protected function getThrowAnnotationIndex(array $tokens, $docBlockStartIndex)
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
    protected function containsComplexThrowToken(array $tokens, $scopeOpener, $scopeCloser)
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
    protected function containsThrowToken(array $tokens, $scopeOpener, $scopeCloser)
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
    protected function isApiMethod(File $phpCsFile, $docBlockStartIndex)
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
