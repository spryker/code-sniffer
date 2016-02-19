<?php
/**
 * (c) Spryker Systems GmbH copyright protected.
 */

namespace Spryker\Sniffs\Commenting;

/**
 * No alignment for @param, @throws, @return, @var, and @type phpdoc tags.
 */
class DocBlockNoInlineAlignmentSniff
{

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register() {
        return [
            T_DOC_COMMENT_TAG,
            T_DOC_COMMENT_STRING,
        ];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the
     *                                        stack passed in $tokens.
     * @return void
     */
    public function process(\PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] === T_DOC_COMMENT_STRING) {
            $this->checkDescription($phpcsFile, $stackPtr);
        }
        if ($tokens[$stackPtr]['code'] === T_DOC_COMMENT_TAG) {
            $this->checkTag($phpcsFile, $stackPtr);
        }
    }

    /**
     * @param \PHP_CodeSniffer_File $phpcsFile
     * @param int $stackPtr
     * @return void
     */
    protected function checkTag(\PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $followingWhitespace = $phpcsFile->findNext(T_DOC_COMMENT_WHITESPACE, $stackPtr + 1, $stackPtr + 2);
        if (!$followingWhitespace) {
            return;
        }

        $content = $tokens[$followingWhitespace]['content'];
        if ($content === ' ') {
            return;
        }

        $fix = $phpcsFile->addFixableError('There should be no additional whitespace around doc block tag types.', $stackPtr);
        if ($fix) {
            $phpcsFile->fixer->replaceToken($followingWhitespace, ' ');
        }
    }

    /**
     * @param \PHP_CodeSniffer_File $phpcsFile
     * @param int $stackPtr
     * @return void
     */
    protected function checkDescription(\PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $content = $tokens[$stackPtr]['content'];

        if (!preg_match('/\s\s+/', $content)) {
            return;
        }

        $fix = $phpcsFile->addFixableError('There should be no inline alignment in doc blocks descriptions.', $stackPtr);
        if ($fix) {
            $newContent = preg_replace('/\s\s+/', ' ', $content);
            $phpcsFile->fixer->replaceToken($stackPtr, $newContent);
        }
    }

}
