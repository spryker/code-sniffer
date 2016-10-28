<?php
namespace Spryker\Sniffs\WhiteSpace;

use PHP_CodeSniffer_File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Always remove more than two empty lines.
 *
 * @author Mark Scherer
 * @license MIT
 */
class EmptyLinesSniff extends AbstractSprykerSniff
{

    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = [
    'PHP',
    'JS',
    'CSS',
    ];

    /**
     * @inheritDoc
     */
    public function register()
    {
        return [T_WHITESPACE];
    }

    /**
     * @inheritDoc
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $this->assertMaximumOneEmptyLineBetweenContent($phpcsFile, $stackPtr);
    }

    /**
     * @param \PHP_CodeSniffer_File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function assertMaximumOneEmptyLineBetweenContent(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['content'] === $phpcsFile->eolChar
            && isset($tokens[($stackPtr + 1)]) === true
            && $tokens[($stackPtr + 1)]['content'] === $phpcsFile->eolChar
            && isset($tokens[($stackPtr + 2)]) === true
            && $tokens[($stackPtr + 2)]['content'] === $phpcsFile->eolChar
        ) {
            $error = 'Found more than a single empty line between content';
            $fix = $phpcsFile->addFixableError($error, ($stackPtr + 3), 'EmptyLines');
            if ($fix) {
                $phpcsFile->fixer->replaceToken($stackPtr + 2, '');
            }
        }
    }

}
