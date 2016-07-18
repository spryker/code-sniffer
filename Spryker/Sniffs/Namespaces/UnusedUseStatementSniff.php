<?php
namespace Spryker\Sniffs\Namespaces;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Tools\Traits\CommentingTrait;
use Spryker\Tools\Traits\NamespaceTrait;

/**
 * Checks for "use" statements that are not needed in a file.
 */
class UnusedUseStatementSniff extends AbstractSprykerSniff
{

    use CommentingTrait;
    use NamespaceTrait;

    /**
     * @inheritDoc
     */
    public function register()
    {
        return [T_USE];
    }

    /**
     * @inheritDoc
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if ($this->shouldIgnoreUse($phpcsFile, $stackPtr)) {
            return;
        }

        $semicolonIndex = $phpcsFile->findEndOfStatement($stackPtr);
        if ($tokens[$semicolonIndex]['code'] !== T_SEMICOLON) {
            return;
        }

        $classNameIndex = $phpcsFile->findPrevious(
            PHP_CodeSniffer_Tokens::$emptyTokens,
            ($semicolonIndex - 1),
            null,
            true
        );

        // Seek along the statement to get the last part, which is the class/interface name.
        while (isset($tokens[($classNameIndex + 1)]) === true
        && in_array($tokens[($classNameIndex + 1)]['code'], [T_STRING, T_NS_SEPARATOR])
        ) {
            $classNameIndex++;
        }

        if ($tokens[$classNameIndex]['code'] !== T_STRING) {
            return;
        }

        if (!$this->isClassUnused($phpcsFile, $classNameIndex)) {
            return;
        }
        if (!$this->isClassNotUsedInDocBlock($phpcsFile, $classNameIndex)) {
            return;
        }

        $warning = 'Unused use statement';
        $fix = $phpcsFile->addFixableWarning($warning, $stackPtr, 'UnusedUse');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        for ($i = $stackPtr; $i <= $semicolonIndex; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        // Also remove whitespace after the semicolon (new lines).
        while (!empty($tokens[$i]) && $tokens[$i]['code'] === T_WHITESPACE) {
            $phpcsFile->fixer->replaceToken($i, '');
            if (strpos($tokens[$i]['content'], $phpcsFile->eolChar) !== false) {
                break;
            }

            $i++;
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer_File $phpcsFile
     * @param int $classNameIndex
     *
     * @return bool
     */
    protected function isClassUnused(PHP_CodeSniffer_File $phpcsFile, $classNameIndex)
    {
        $tokens = $phpcsFile->getTokens();

        $classUsed = $phpcsFile->findNext(T_STRING, ($classNameIndex + 1), null, false, $tokens[$classNameIndex]['content']);

        while ($classUsed !== false) {
            $beforeUsage = $phpcsFile->findPrevious(
                PHP_CodeSniffer_Tokens::$emptyTokens,
                ($classUsed - 1),
                null,
                true
            );
            // If a backslash is used before the class name then this is some other use statement.
            if ($tokens[$beforeUsage]['code'] !== T_USE && $tokens[$beforeUsage]['code'] !== T_NS_SEPARATOR) {
                return false;
            }

            // Trait use statement within a class.
            if ($tokens[$beforeUsage]['code'] === T_USE && !empty($tokens[$beforeUsage]['conditions'])) {
                return false;
            }

            $classUsed = $phpcsFile->findNext(T_STRING, ($classUsed + 1), null, false, $tokens[$classNameIndex]['content']);
        }

        return true;
    }

    /**
     * @param \PHP_CodeSniffer_File $phpcsFile
     *
     * @return bool
     */
    protected function isClassNotUsedInDocBlock(PHP_CodeSniffer_File $phpcsFile, $classNameIndex)
    {
        $tokens = $phpcsFile->getTokens();

        $docBlockStartIndex = $phpcsFile->findNext(T_DOC_COMMENT_OPEN_TAG, ($classNameIndex + 1), null, false);
        while ($docBlockStartIndex !== false) {
            if (empty($tokens[$docBlockStartIndex]['comment_closer'])) {
                continue;
            }
            $docBlockEndIndex = $tokens[$docBlockStartIndex]['comment_closer'];
            for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
                if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_STRING') {
                    continue;
                }

                $hints = $this->getNonFullyQualifiedHints($tokens[$i]['content']);
                if (!$hints) {
                    continue;
                }

                if (in_array($tokens[$classNameIndex]['content'], $hints)) {
                    return false;
                }
            }

            $docBlockStartIndex = $phpcsFile->findNext(T_DOC_COMMENT_OPEN_TAG, ($tokens[$docBlockStartIndex]['comment_closer'] + 1), null, false);
        }

        return true;
    }

    /**
     * @param string $content
     *
     * @return array
     */
    protected function getNonFullyQualifiedHints($content)
    {
        $hints = $this->extractHints($content);
        foreach ($hints as $key => $hint) {
            if (strpos($hint, '\\') !== 0) {
                continue;
            }

            unset($hints[$key]);
        }

        return $hints;
    }

    /**
     * @param string $content
     *
     * @return array
     */
    protected function extractHints($content)
    {
        $hint = $content;
        if (strpos($hint, ' ') !== false) {
            list($hint) = explode(' ', $content, 2);
        }

        if (strpos($hint, '|') === false) {
            return (array)$hint;
        }

        $pieces = explode('|', $hint);

        $hints = [];
        foreach ($pieces as $piece) {
            $hints[] = trim($piece);
        }

        return $hints;
    }

}
