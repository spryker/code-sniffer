<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Tools\Traits\CommentingTrait;

/**
 * Methods that may not return anything need to be declared as `@return void`.
 * Constructor and destructor may not have this addition, as they cannot return by definition.
 */
class DocBlockReturnVoidSniff extends AbstractSprykerSniff
{
    use CommentingTrait;

    /**
     * @var string[]
     */
    protected $ignored = [
        '__construct',
        '__destruct',
    ];

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_FUNCTION];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);
        if (in_array($tokens[$nextIndex]['content'], $this->ignored, true)) {
            $this->checkConstructorAndDestructor($phpcsFile, $nextIndex);

            return;
        }

        // Don't mess with closures
        $prevIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
        if (!$this->isGivenKind(Tokens::$methodPrefixes, $tokens[$prevIndex])) {
            return;
        }

        $docBlockEndIndex = $this->findRelatedDocBlock($phpcsFile, $stackPtr);
        if (!$docBlockEndIndex) {
            return;
        }

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        $docBlockReturnIndex = $this->findDocBlockReturn($phpcsFile, $docBlockStartIndex, $docBlockEndIndex);

        $hasInheritDoc = $this->hasInheritDoc($phpcsFile, $docBlockStartIndex, $docBlockEndIndex);

        // If interface we will at least report it
        if (empty($tokens[$stackPtr]['scope_opener']) || empty($tokens[$stackPtr]['scope_closer'])) {
            if (!$docBlockReturnIndex && !$hasInheritDoc) {
                $phpcsFile->addError('Method does not have a return statement in doc block: ' . $tokens[$nextIndex]['content'], $nextIndex, 'ReturnMissingInInterface');
            }

            return;
        }

        // If inheritDoc is present assume the parent contains it
        if (!$docBlockReturnIndex && $hasInheritDoc) {
            return;
        }

        // We only look for void methods right now
        $returnType = $this->detectReturnTypeVoid($phpcsFile, $stackPtr);

        if ($docBlockReturnIndex) {
            $this->assertExisting($phpcsFile, $stackPtr, $docBlockReturnIndex, $returnType);
            $this->assertTypeHint($phpcsFile, $stackPtr, $docBlockReturnIndex);

            return;
        }

        if ($returnType === null) {
            $phpcsFile->addError('Method does not have a return statement in doc block: ' . $tokens[$nextIndex]['content'], $nextIndex, 'ReturnMissing');

            return;
        }

        $fix = $phpcsFile->addFixableError('Method does not have a return void statement in doc block: ' . $tokens[$nextIndex]['content'], $nextIndex, 'ReturnVoidMissing');
        if (!$fix) {
            return;
        }

        $this->addReturnAnnotation($phpcsFile, $docBlockStartIndex, $docBlockEndIndex, $returnType);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $index
     *
     * @return void
     */
    protected function checkConstructorAndDestructor(File $phpcsFile, int $index): void
    {
        $docBlockEndIndex = $this->findRelatedDocBlock($phpcsFile, $index);
        if (!$docBlockEndIndex) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        $docBlockReturnIndex = $this->findDocBlockReturn($phpcsFile, $docBlockStartIndex, $docBlockEndIndex);
        if (!$docBlockReturnIndex) {
            return;
        }

        $fix = $phpcsFile->addFixableError($tokens[$index]['content'] . ' has invalid return statement.', $docBlockReturnIndex, 'ReturnStatementInvalid');
        if ($fix) {
            $phpcsFile->fixer->replaceToken($docBlockReturnIndex, '');

            $possibleStringToken = $tokens[$docBlockReturnIndex + 2];
            if ($this->isGivenKind(T_DOC_COMMENT_STRING, $possibleStringToken)) {
                $phpcsFile->fixer->replaceToken($docBlockReturnIndex + 1, '');
                $phpcsFile->fixer->replaceToken($docBlockReturnIndex + 2, '');
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return int|null
     */
    protected function findDocBlockReturn(File $phpcsFile, int $docBlockStartIndex, int $docBlockEndIndex): ?int
    {
        $tokens = $phpcsFile->getTokens();

        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if (!$this->isGivenKind(T_DOC_COMMENT_TAG, $tokens[$i])) {
                continue;
            }
            if ($tokens[$i]['content'] !== '@return') {
                continue;
            }

            return $i;
        }

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     * @param string $returnType
     *
     * @return void
     */
    protected function addReturnAnnotation(
        File $phpcsFile,
        int $docBlockStartIndex,
        int $docBlockEndIndex,
        string $returnType = 'void'
    ): void {
        $indentation = $this->getIndentationWhitespace($phpcsFile, $docBlockEndIndex);

        $lastLineEndIndex = $phpcsFile->findPrevious([T_DOC_COMMENT_WHITESPACE], $docBlockEndIndex - 1, null, true);

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->addNewline($lastLineEndIndex);
        $phpcsFile->fixer->addContent($lastLineEndIndex, $indentation . '* @return ' . $returnType);
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * For right now we only try to detect void inside function/method.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $index
     *
     * @return string|null
     */
    protected function detectReturnTypeVoid(File $phpcsFile, int $index): ?string
    {
        $tokens = $phpcsFile->getTokens();

        $type = 'void';

        $methodStartIndex = $tokens[$index]['scope_opener'];
        $methodEndIndex = $tokens[$index]['scope_closer'];

        for ($i = $methodStartIndex + 1; $i < $methodEndIndex; ++$i) {
            if ($this->isGivenKind([T_FUNCTION], $tokens[$i])) {
                $endIndex = $tokens[$i]['scope_closer'];
                $i = $endIndex;

                continue;
            }

            if (!$this->isGivenKind([T_RETURN], $tokens[$i])) {
                continue;
            }

            $nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $i + 1, null, true);
            if (!$this->isGivenKind(T_SEMICOLON, $tokens[$nextIndex])) {
                return null;
            }
        }

        return $type;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $pointer
     * @param int $docBlockReturnIndex
     * @param string|null $returnType
     *
     * @return void
     */
    protected function assertExisting(
        File $phpcsFile,
        int $pointer,
        int $docBlockReturnIndex,
        ?string $returnType
    ): void {
        $tokens = $phpcsFile->getTokens();

        $documentedReturnType = $this->documentedReturnType($tokens, $docBlockReturnIndex);

        if ($returnType !== 'void' || $documentedReturnType === 'void') {
            return;
        }
        if ($this->documentedTypesContainFuzzyVoid($documentedReturnType)) {
            return;
        }
        if ($this->bodyContainsYield($phpcsFile, $pointer)) {
            return;
        }

        // We need to skip for fake extension hooks.
        $scopeOpenerIndex = $tokens[$pointer]['scope_opener'];
        $firstToken = $phpcsFile->findNext(Tokens::$emptyTokens, $scopeOpenerIndex + 1, null, true);
        if ($tokens[$firstToken]['code'] === T_THROW) {
            return;
        }

        $phpcsFile->addError('Method is void, but doc block states otherwise.', $docBlockReturnIndex + 2, 'InvalidVoidBody');
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     * @param int $docBlockReturnIndex
     *
     * @return void
     */
    protected function assertTypeHint(File $phpcsFile, int $stackPtr, int $docBlockReturnIndex): void
    {
        $tokens = $phpcsFile->getTokens();

        $parenthesisCloserIndex = $tokens[$stackPtr]['parenthesis_closer'];
        $scopeOpenerIndex = $tokens[$stackPtr]['scope_opener'];
        $nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $parenthesisCloserIndex + 1, $scopeOpenerIndex, true);

        if ($tokens[$nextIndex]['code'] !== T_COLON) {
            return;
        }

        $documentedReturnType = $this->documentedReturnType($tokens, $docBlockReturnIndex);

        $typeHintIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $nextIndex + 1, $scopeOpenerIndex, true);
        if (!$typeHintIndex) {
            return;
        }

        // ?... cannot be about void anymore
        $nullable = false;
        if ($tokens[$typeHintIndex]['code'] === T_NULLABLE) {
            $nullable = true;
            $typeHintIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $typeHintIndex + 1, $scopeOpenerIndex, true);
            if (!$typeHintIndex) {
                return;
            }
        }

        $typehint = $tokens[$typeHintIndex]['content'];
        if ($nullable) {
            $typehint = '?' . $typehint;
        }

        if ($documentedReturnType !== 'void' && $typeHintIndex !== 'void') {
            return;
        }
        if ($documentedReturnType === $typehint) {
            return;
        }

        $message = sprintf('Return typehint `%s` does not match documented return type `%s`.', $typehint, $documentedReturnType);
        $phpcsFile->addError($message, $typeHintIndex, 'ReturnTypeMismatch');
    }

    /**
     * @param string $documentedReturnType
     *
     * @return bool
     */
    protected function documentedTypesContainFuzzyVoid(string $documentedReturnType): bool
    {
        $types = explode('|', $documentedReturnType);

        return in_array('null', $types, true);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $pointer
     *
     * @return bool
     */
    protected function bodyContainsYield(File $phpcsFile, int $pointer): bool
    {
        $tokens = $phpcsFile->getTokens();

        $methodStartIndex = $tokens[$pointer]['scope_opener'];
        $methodEndIndex = $tokens[$pointer]['scope_closer'];

        for ($i = $methodStartIndex + 1; $i < $methodEndIndex; ++$i) {
            if ($this->isGivenKind([T_YIELD, T_YIELD_FROM], $tokens[$i])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $tokens
     * @param int $docBlockReturnIndex
     *
     * @return string
     */
    protected function documentedReturnType(array $tokens, int $docBlockReturnIndex): string
    {
        $documentedReturnType = $tokens[$docBlockReturnIndex + 2]['content'];
        $whiteSpacePosition = mb_strpos($documentedReturnType, ' ');
        if ($whiteSpacePosition !== false) {
            $documentedReturnType = mb_substr($documentedReturnType, 0, $whiteSpacePosition);
        }

        return $documentedReturnType;
    }
}
