<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spryker\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Makes sure we do not have any trailing commas in single line declarations/calls.
 */
class DisallowTrailingCommaInSingleLineSniff implements Sniff
{
    /**
     * @var array<string|int>
     */
    protected $closingTokens = [
        T_CLOSE_PARENTHESIS,
        T_CLOSE_SQUARE_BRACKET,
    ];

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_COMMA,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $commaIndex): void
    {
        $tokens = $phpcsFile->getTokens();

        $nextIndex = $phpcsFile->findNext(T_WHITESPACE, ($commaIndex + 1), null, true);
        if (!$nextIndex || $tokens[$nextIndex]['line'] !== $tokens[$commaIndex]['line']) {
            return;
        }

        if (!in_array($tokens[$nextIndex]['code'], $this->closingTokens, true)) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'Trailing comma in single line is disallowed.',
            $commaIndex,
            'TrailingCommaDisallowed',
        );

        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($commaIndex, '');
        $phpcsFile->fixer->endChangeset();
    }
}
