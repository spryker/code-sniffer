<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Traits\CommentingTrait;
use Spryker\Traits\SignatureTrait;

/**
 * Methods always need doc blocks.
 * Constructor and destructor may not have one if they do not have arguments.
 */
class DocBlockSniff extends AbstractSprykerSniff
{
    use CommentingTrait;
    use SignatureTrait;

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
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);

        if ($nextIndex === false) {
            return;
        }

        if ($tokens[$nextIndex]['content'] === '__construct' || $tokens[$nextIndex]['content'] === '__destruct') {
            $this->checkConstructorAndDestructor($phpcsFile, $stackPtr);

            return;
        }

        // Don't mess with closures
        $prevIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
        if (!$this->isGivenKind(Tokens::$methodPrefixes, $tokens[$prevIndex])) {
            return;
        }

        $docBlockEndIndex = $this->findRelatedDocBlock($phpcsFile, $stackPtr);
        if ($docBlockEndIndex) {
            return;
        }

        // We only look for void methods right now
        $returnType = $this->detectReturnTypeVoid($phpcsFile, $stackPtr);
        if ($returnType === null) {
            $phpcsFile->addError('Method does not have a doc block: ' . $tokens[$nextIndex]['content'] . '()', $nextIndex, 'DocBlockMissing');

            return;
        }

        $fix = $phpcsFile->addFixableError('Method does not have a docblock with return void statement: ' . $tokens[$nextIndex]['content'], $nextIndex, 'ReturnVoidMissing');
        if (!$fix) {
            return;
        }

        $this->addDocBlock($phpcsFile, $stackPtr, $returnType);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $index
     * @param string $returnType
     *
     * @return void
     */
    protected function addDocBlock(File $phpcsFile, int $index, string $returnType): void
    {
        $tokens = $phpcsFile->getTokens();

        $firstTokenOfLine = $this->getFirstTokenOfLine($tokens, $index);

        $prevContentIndex = $phpcsFile->findPrevious(T_WHITESPACE, $firstTokenOfLine - 1, null, true);

        if ($prevContentIndex === false) {
            return;
        }

        if ($tokens[$prevContentIndex]['type'] === 'T_ATTRIBUTE_END') {
            $firstTokenOfLine = $this->getFirstTokenOfLine($tokens, $prevContentIndex);
        }

        $indentation = $this->getIndentationWhitespace($phpcsFile, $index);

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->addNewlineBefore($firstTokenOfLine);
        $phpcsFile->fixer->addContentBefore($firstTokenOfLine, $indentation . ' */');
        $phpcsFile->fixer->addNewlineBefore($firstTokenOfLine);
        $phpcsFile->fixer->addContentBefore($firstTokenOfLine, $indentation . ' * @return ' . $returnType);
        $phpcsFile->fixer->addNewlineBefore($firstTokenOfLine);
        $phpcsFile->fixer->addContentBefore($firstTokenOfLine, $indentation . '/**');
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkConstructorAndDestructor(File $phpcsFile, int $stackPtr): void
    {
        $docBlockEndIndex = $this->findRelatedDocBlock($phpcsFile, $stackPtr);
        if ($docBlockEndIndex) {
            return;
        }

        $methodSignature = $this->getMethodSignature($phpcsFile, $stackPtr);
        $arguments = count($methodSignature);
        if (!$arguments) {
            return;
        }

        $phpcsFile->addError('Missing doc block for method', $stackPtr, 'ConstructDesctructMissingDocBlock');
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
     * For right now we only try to detect void.
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

        if (empty($tokens[$index]['scope_opener'])) {
            return null;
        }

        $methodStartIndex = $tokens[$index]['scope_opener'];
        $methodEndIndex = $tokens[$index]['scope_closer'];

        for ($i = $methodStartIndex + 1; $i < $methodEndIndex; ++$i) {
            if ($this->isGivenKind([T_FUNCTION, T_CLOSURE], $tokens[$i])) {
                $endIndex = $tokens[$i]['scope_closer'];
                if (!empty($tokens[$i]['nested_parenthesis'])) {
                    $endIndex = array_pop($tokens[$i]['nested_parenthesis']);
                }
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
}
