<?php
/**
 * (c) Spryker Systems GmbH copyright protected.
 */

namespace Spryker\Sniffs\Commenting;


use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Methods that may not return anything need to be declared as `@return void`.
 * Constructor and destructor may not have this addition, as they cannot return by definition.
 */
class DocBlockReturnVoidSniff extends AbstractSprykerSniff
{

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register() {
        return [T_FUNCTION];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer_File $phpcsFile All the tokens found in the document.
     * @param int $stackPtr The position of the current token
     *    in the stack passed in $tokens.
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $nextIndex = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, $stackPtr + 1, null, true);
        if ($tokens[$nextIndex]['content'] === '__construct' || $tokens[$nextIndex]['content'] === '__destruct') {
            $this->checkConstructorAndDestructor($phpcsFile, $nextIndex);
            return;
        }

        // Don't mess with closures
        $prevIndex = $phpcsFile->findPrevious(PHP_CodeSniffer_Tokens::$emptyTokens, $stackPtr - 1, null, true);
        if (!$this->isGivenKind(PHP_CodeSniffer_Tokens::$methodPrefixes, $tokens[$prevIndex])) {
            return;
        }

        // We only look for void methods right now
        $returnType = $this->detectReturnTypeVoid($phpcsFile, $stackPtr);
        if ($returnType === null) {
            return;
        }

        $docBlockEndIndex = $this->findRelatedDocBlock($phpcsFile, $stackPtr);
        if (!$docBlockEndIndex) {
            $phpcsFile->addError('Method does not have a doc block: ' . $tokens[$nextIndex]['content'], $nextIndex);
            //$this->addNewDocBlock($tokens, $docBlockIndex, $returnType);
            return;
        }

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        $docBlockReturnIndex = $this->findDocBlockReturn($phpcsFile, $docBlockStartIndex,$docBlockEndIndex);
        if (!$docBlockReturnIndex) {
            // For now
            $phpcsFile->addError('Method does not have a return statement in doc block: ' . $tokens[$nextIndex]['content'], $nextIndex);
            //$this->addReturnAnnotation($docBlock, $returnType);
            return;
        }
    }

    /**
     * @param \PHP_CodeSniffer_File $phpcsFile
     * @param int $index
     * @return void
     */
    protected function checkConstructorAndDestructor(PHP_CodeSniffer_File $phpcsFile, $index) {
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

        $fix = $phpcsFile->addFixableError($tokens[$index]['content'] . ' has invalid return statement.', $docBlockReturnIndex);
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
     * @param \PHP_CodeSniffer_File $phpcsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return int|null
     */
    protected function findDocBlockReturn(PHP_CodeSniffer_File $phpcsFile, $docBlockStartIndex, $docBlockEndIndex) {
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
     * @param \Symfony\CS\DocBlock\DocBlock $doc
     *
     * @return void
     */
    protected function addReturnAnnotation(DocBlock $doc, $returnType = 'void')
    {
        $lines = $doc->getLines();
        $count = count($lines);

        $lastLine = $doc->getLine($count - 1);
        $lastLineContent = $lastLine['content'];
        $whiteSpaceLength = strlen($lastLineContent) - 2;

        $returnLine = str_repeat(' ', $whiteSpaceLength) . '* @return ' . $returnType;
        $lastLineContent = $returnLine . "\n" . $lastLineContent;

        $lastLine->setContent($lastLineContent);
    }

    /**
     * For right now we only try to detect void.
     *
     * @param int $index
     *
     * @return string|null
     */
    protected function detectReturnTypeVoid(PHP_CodeSniffer_File $phpcsFile, $index)
    {
        $tokens = $phpcsFile->getTokens();

        $type = 'void';

        $methodStartIndex = $tokens[$index]['scope_opener'];
        if (!$methodStartIndex) {
            return null;
        }
        $methodEndIndex = $tokens[$index]['scope_closer'];
        if (!$methodEndIndex) {
            return null;
        }

        for ($i = $methodStartIndex + 1; $i < $methodEndIndex; ++$i) {
            if ($this->isGivenKind([T_FUNCTION], $tokens[$i])) {
                $endIndex = $tokens[$i]['scope_closer'];
                $i = $endIndex;
                continue;
            }

            if (!$this->isGivenKind([T_RETURN], $tokens[$i])) {
                continue;
            }

            $nextIndex = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, $i + 1, null, true);
            if (!$this->isGivenKind(T_SEMICOLON, $tokens[$nextIndex])) {
                return null;
            }
        }

        return $type;
    }

    /**
     * @param int $docBlockIndex
     * @param string $returnType
     *
     * @return void
     */
    protected function addNewDocBlock(PHP_CodeSniffer_File $phpcsFile, $docBlockIndex, $returnType)
    {
        $tokens = $phpcsFile->getTokens();

        $docBlockTemplate = <<<TXT
/**
     * @return $returnType
     */

TXT;
        $docBlockTemplate = $docBlockTemplate . '    ' . $tokens[$docBlockIndex]['content'];

        $tokens[$docBlockIndex]->setContent($docBlockTemplate);
    }

}
