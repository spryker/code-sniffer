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

        $classUsed = $phpcsFile->findNext(T_STRING, ($classNameIndex + 1), null, false, $tokens[$classNameIndex]['content']);

        while ($classUsed !== false) {
            $beforeUsage = $phpcsFile->findPrevious(
                PHP_CodeSniffer_Tokens::$emptyTokens,
                ($classUsed - 1),
                null,
                true
            );
         // If a backslash is used before the class name then this is some other
         // use statement.
            if ($tokens[$beforeUsage]['code'] !== T_USE && $tokens[$beforeUsage]['code'] !== T_NS_SEPARATOR) {
                return;
            }

         // Trait use statement within a class.
            if ($tokens[$beforeUsage]['code'] === T_USE && !empty($tokens[$beforeUsage]['conditions'])) {
                return;
            }

            $classUsed = $phpcsFile->findNext(T_STRING, ($classUsed + 1), null, false, $tokens[$classNameIndex]['content']);
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

}
