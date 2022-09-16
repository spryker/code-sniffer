<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerStrict\Sniffs\TypeHints;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\TokenHelper;

class DeclareNoStrictTypesAfterOpenTagSniff implements Sniff
{
    public const CODE_DECLARE_STRICT_TYPES_EXISTING = 'DeclareStrictTypesExisting';

    /**
     * @return array<int, (int|string)>
     */
    public function register(): array
    {
        return [
            T_OPEN_TAG,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        if (TokenHelper::findPrevious($phpcsFile, T_DOC_COMMENT_CLOSE_TAG, $stackPtr - 1) !== null) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $declarePointer = TokenHelper::findNextEffective($phpcsFile, $stackPtr + 1);

        if ($declarePointer === null) {
            return;
        }

        for ($i = $stackPtr + 1; $i < $declarePointer; ++$i) {
            if ($tokens[$i]['type'] !== 'T_WHITESPACE') {
                return;
            }
        }

        $strictTypesPointer = null;

        if ($tokens[$declarePointer]['code'] === T_DECLARE) {
            for ($i = $tokens[$declarePointer]['parenthesis_opener'] + 1; $i < $tokens[$declarePointer]['parenthesis_closer']; $i++) {
                if ($tokens[$i]['code'] !== T_STRING || $tokens[$i]['content'] !== 'strict_types') {
                    continue;
                }

                $strictTypesPointer = $i;
                break;
            }
        }

        if ($strictTypesPointer === null) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            'declare(strict_types=1) exists, but shouldn\'t',
            $stackPtr,
            self::CODE_DECLARE_STRICT_TYPES_EXISTING
        );
        if ($fix) {
            $phpcsFile->fixer->beginChangeset();

            // Remove all tokens that relate to declare strict_types
            for ($i = $tokens[$declarePointer]['parenthesis_opener'] - 1; $i <= $tokens[$declarePointer]['parenthesis_closer'] + 1; $i++) {
                $phpcsFile->fixer->replaceToken($i, '');
            }

            // Remove all empty lines before declare strict_types
            $whitespace = true;
            $i = $tokens[$declarePointer]['parenthesis_opener'] - 1;
            do {
                $phpcsFile->fixer->replaceToken($i, '');
                --$i;
                if ($tokens[$i]['type'] !== 'T_WHITESPACE') {
                    $whitespace = false;
                }
            } while ($whitespace);

            // Remove all empty lines after declare strict_types
            $whitespace = true;
            $i = $tokens[$declarePointer]['parenthesis_closer'] + 1;
            do {
                $phpcsFile->fixer->replaceToken($i, '');
                ++$i;
                if ($tokens[$i]['type'] !== 'T_WHITESPACE') {
                    $whitespace = false;
                }
            } while ($whitespace);

            $phpcsFile->fixer->addNewline($i - 1);
            $phpcsFile->fixer->endChangeset();
        }
    }
}
