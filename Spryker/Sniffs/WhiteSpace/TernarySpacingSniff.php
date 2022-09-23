<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Verifies that short ternary operators have valid spacing surrounding them.
 *
 * @author Mark Scherer
 * @license MIT
 */
class TernarySpacingSniff extends AbstractSprykerSniff
{
    /**
     * @var array<string>
     */
    public $supportedTokenizers = [
        'PHP',
    ];

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_INLINE_THEN,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $nextIndex = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
        if (!$nextIndex || $nextIndex - 1 === $stackPtr || $tokens[$nextIndex]['code'] !== T_INLINE_ELSE) {
            return;
        }

        if ($tokens[$nextIndex - 1]['code'] !== T_WHITESPACE) {
            return;
        }

        $fix = $phpcsFile->addFixableError('Additional whitespace found between short ternary', $stackPtr, 'InvalidWhitespace');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($nextIndex - 1, '');
        $phpcsFile->fixer->endChangeset();
    }
}
