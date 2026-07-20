<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerStrict\Sniffs\TypeHints;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Every class constant must declare a native type hint. A `const array` carrying an
 * `array<...>`/`list<...>` shape docblock is a valid native type and is not flagged.
 * The value is not checked: PHP already guarantees a typed constant's value matches its
 * declared type at compile time.
 */
class ClassConstantTypeHintSniff implements Sniff
{
    protected const string CODE_MISSING_NATIVE_TYPE_HINT = 'MissingNativeTypeHint';

    protected const string MESSAGE_MISSING_NATIVE_TYPE_HINT = 'Class constant "%s" must have a native type hint.';

    /**
     * @var array<int|string>
     */
    protected const array CLASS_LIKE_TOKENS = [T_CLASS, T_ANON_CLASS, T_INTERFACE, T_TRAIT, T_ENUM];

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_CONST];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        if (!$phpcsFile->hasCondition($stackPtr, static::CLASS_LIKE_TOKENS)) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        // findEndOfStatement() stops at the first comma, which would truncate a grouped
        // `const A = 1, B = 2;` declaration; use the terminating semicolon instead.
        $statementEnd = $phpcsFile->findNext(T_SEMICOLON, $stackPtr + 1);
        if ($statementEnd === false) {
            return;
        }

        $namePtr = $this->findFirstConstantNamePtr($phpcsFile, $stackPtr, $statementEnd);
        if ($namePtr === null) {
            return;
        }

        if ($this->readDeclaredType($phpcsFile, $stackPtr, $namePtr) !== '') {
            return;
        }

        // A single `const` statement may declare several constants (const A = 1, B = 2);
        // none of them can carry a native type, so report each name individually.
        foreach ($this->findConstantNamePtrs($phpcsFile, $stackPtr, $statementEnd) as $constantNamePtr) {
            $phpcsFile->addError(
                static::MESSAGE_MISSING_NATIVE_TYPE_HINT,
                $constantNamePtr,
                static::CODE_MISSING_NATIVE_TYPE_HINT,
                [$tokens[$constantNamePtr]['content']],
            );
        }
    }

    protected function findFirstConstantNamePtr(File $phpcsFile, int $constPtr, int $statementEnd): ?int
    {
        $equalPtr = $phpcsFile->findNext(T_EQUAL, $constPtr + 1, $statementEnd);
        if ($equalPtr === false) {
            return null;
        }

        $namePtr = $phpcsFile->findPrevious(T_STRING, $equalPtr - 1, $constPtr);

        return $namePtr === false ? null : $namePtr;
    }

    /**
     * @return array<int>
     */
    protected function findConstantNamePtrs(File $phpcsFile, int $constPtr, int $statementEnd): array
    {
        $tokens = $phpcsFile->getTokens();
        $namePtrs = [];

        for ($i = $constPtr + 1; $i < $statementEnd; $i++) {
            if ($tokens[$i]['code'] !== T_STRING) {
                continue;
            }

            $nextPtr = $phpcsFile->findNext(Tokens::$emptyTokens, $i + 1, $statementEnd, true);
            if ($nextPtr !== false && $tokens[$nextPtr]['code'] === T_EQUAL) {
                $namePtrs[] = $i;
            }
        }

        return $namePtrs;
    }

    protected function readDeclaredType(File $phpcsFile, int $constPtr, int $namePtr): string
    {
        $tokens = $phpcsFile->getTokens();
        $type = '';

        for ($i = $constPtr + 1; $i < $namePtr; $i++) {
            if (isset(Tokens::$emptyTokens[$tokens[$i]['code']])) {
                continue;
            }

            $type .= $tokens[$i]['content'];
        }

        return $type;
    }
}
