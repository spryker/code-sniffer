<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerStrict\Sniffs\TypeHints;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\TokenHelper;

class DeclareStrictTypesAfterFileDocSniff implements Sniff
{
    public const CODE_DECLARE_STRICT_TYPES_MISSING = 'DeclareStrictTypesMissing';

    public const CODE_INCORRECT_STRICT_TYPES_FORMAT = 'IncorrectStrictTypesFormat';

    public const CODE_INCORRECT_WHITESPACE_BEFORE_DECLARE = 'IncorrectWhitespaceBeforeDeclare';

    public const CODE_INCORRECT_WHITESPACE_AFTER_DECLARE = 'IncorrectWhitespaceAfterDeclare';

    /** @var int */
    public $linesCountBeforeDeclare = 1;

    /** @var int */
    public $linesCountAfterDeclare = 1;

    /** @var int */
    public $spacesCountAroundEqualsSign = 1;

    /**
     * @return array<int, (int|string)>
     */
    public function register(): array
    {
        return [
            T_DOC_COMMENT_CLOSE_TAG,
        ];
    }

    /**
     * @return void
     */
    protected function onBeforeProcess(): void
    {
        $this->linesCountBeforeDeclare = $this->normalizeIntValue($this->linesCountBeforeDeclare);
        $this->linesCountAfterDeclare = $this->normalizeIntValue($this->linesCountAfterDeclare);
        $this->spacesCountAroundEqualsSign = $this->normalizeIntValue($this->spacesCountAroundEqualsSign);
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $this->onBeforeProcess();

        if (TokenHelper::findPrevious($phpcsFile, T_DOC_COMMENT_CLOSE_TAG, $stackPtr - 1) !== null) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $declarePointer = TokenHelper::findNextEffective($phpcsFile, $stackPtr + 1);

        if ($declarePointer === null || $tokens[$declarePointer]['code'] !== T_DECLARE) {
            $fix = $phpcsFile->addFixableError(
                sprintf('Missing declare(%s) after file doc.', $this->getStrictTypeDeclaration()),
                $stackPtr,
                self::CODE_DECLARE_STRICT_TYPES_MISSING
            );
            if ($fix) {
                $phpcsFile->fixer->beginChangeset();

                $linesCountBefore = $declarePointer - $stackPtr - 2;
                if ($linesCountBefore <= $this->linesCountBeforeDeclare) {
                    for ($i = 0; $i < $this->linesCountBeforeDeclare - $linesCountBefore; ++$i) {
                        $phpcsFile->fixer->addNewline($declarePointer - 1);
                    }
                }

                $phpcsFile->fixer->addContent(
                    $declarePointer - 1,
                    sprintf('declare(%s);%s', $this->getStrictTypeDeclaration(), $phpcsFile->eolChar)
                );

                if ($this->linesCountAfterDeclare > 0) {
                    for ($i = 0; $i < $this->linesCountAfterDeclare; ++$i) {
                        $phpcsFile->fixer->addNewline($declarePointer - 1);
                    }
                }

                $phpcsFile->fixer->endChangeset();
            }
            return;
        }

        $strictTypesPointer = null;
        for ($i = $tokens[$declarePointer]['parenthesis_opener'] + 1; $i < $tokens[$declarePointer]['parenthesis_closer']; $i++) {
            if ($tokens[$i]['code'] !== T_STRING || $tokens[$i]['content'] !== 'strict_types') {
                continue;
            }

            $strictTypesPointer = (int)$i;
            break;
        }

        if ($strictTypesPointer === null) {
            $fix = $phpcsFile->addFixableError(
                sprintf('Missing declare(%s) after file doc.', $this->getStrictTypeDeclaration()),
                $declarePointer,
                self::CODE_DECLARE_STRICT_TYPES_MISSING
            );
            if ($fix) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->addContentBefore(
                    $tokens[$declarePointer]['parenthesis_closer'],
                    ', ' . $this->getStrictTypeDeclaration()
                );
                $phpcsFile->fixer->endChangeset();
            }
            return;
        }

        /** @var int $numberPointer */
        $numberPointer = TokenHelper::findNext($phpcsFile, T_LNUMBER, $strictTypesPointer + 1);
        if ($tokens[$numberPointer]['content'] !== '1') {
            $fix = $phpcsFile->addFixableError(
                sprintf(
                    'Expected %s, found %s.',
                    $this->getStrictTypeDeclaration(),
                    TokenHelper::getContent($phpcsFile, $strictTypesPointer, $numberPointer)
                ),
                $declarePointer,
                self::CODE_DECLARE_STRICT_TYPES_MISSING
            );
            if ($fix) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->replaceToken($numberPointer, '1');
                $phpcsFile->fixer->endChangeset();
            }
            return;
        }

        $strictTypesContent = TokenHelper::getContent($phpcsFile, $strictTypesPointer, $numberPointer);
        $format = sprintf('strict_types%1$s=%1$s1', str_repeat(' ', $this->spacesCountAroundEqualsSign));
        if ($strictTypesContent !== $format) {
            $fix = $phpcsFile->addFixableError(
                sprintf(
                    'Expected %s, found %s.',
                    $format,
                    $strictTypesContent
                ),
                $strictTypesPointer,
                self::CODE_INCORRECT_STRICT_TYPES_FORMAT
            );
            if ($fix) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->replaceToken($strictTypesPointer, $format);
                for ($i = $strictTypesPointer + 1; $i <= $numberPointer; $i++) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }
                $phpcsFile->fixer->endChangeset();
            }
        }

        $pointerBeforeDeclare = TokenHelper::findPreviousExcluding($phpcsFile, T_WHITESPACE, $declarePointer - 1);

        $whitespaceBefore = '';
        if ($pointerBeforeDeclare === $stackPtr) {
            $whitespaceBefore .= substr($tokens[$stackPtr]['content'], strlen('<?php'));
        }

        if ($pointerBeforeDeclare + 1 !== $declarePointer) {
            $whitespaceBefore .= TokenHelper::getContent($phpcsFile, $pointerBeforeDeclare + 1, $declarePointer - 1);
        }

        $declareOnFirstLine = $tokens[$declarePointer]['line'] === $tokens[$stackPtr]['line'];
        $linesCountBefore = $declareOnFirstLine ? 0 : substr_count($whitespaceBefore, $phpcsFile->eolChar) - 1;
        if ($declareOnFirstLine || $linesCountBefore !== $this->linesCountBeforeDeclare) {
            $fix = $phpcsFile->addFixableError(
                sprintf(
                    'Expected %d line%s before declare statement, found %d.',
                    $this->linesCountBeforeDeclare,
                    $this->linesCountBeforeDeclare === 1 ? '' : 's',
                    $linesCountBefore
                ),
                $declarePointer,
                self::CODE_INCORRECT_WHITESPACE_BEFORE_DECLARE
            );
            if ($fix) {
                $phpcsFile->fixer->beginChangeset();

                for ($i = $pointerBeforeDeclare + 1; $i < $declarePointer; $i++) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }
                if ($pointerBeforeDeclare !== null) {
                    for ($i = 0; $i <= $this->linesCountBeforeDeclare; $i++) {
                        $phpcsFile->fixer->addNewline($pointerBeforeDeclare);
                    }
                }
                $phpcsFile->fixer->endChangeset();
            }
        }

        /** @var int $declareSemicolonPointer */
        $declareSemicolonPointer = TokenHelper::findNextEffective($phpcsFile, $tokens[$declarePointer]['parenthesis_closer'] + 1);
        $pointerAfterWhitespaceEnd = TokenHelper::findNextExcluding($phpcsFile, T_WHITESPACE, $declareSemicolonPointer + 1);
        if ($pointerAfterWhitespaceEnd === null) {
            return;
        }

        $whitespaceAfter = TokenHelper::getContent($phpcsFile, $declareSemicolonPointer + 1, $pointerAfterWhitespaceEnd - 1);

        $newLinesAfter = substr_count($whitespaceAfter, $phpcsFile->eolChar);
        $linesCountAfter = $newLinesAfter > 0 ? $newLinesAfter - 1 : 0;

        if ($linesCountAfter === $this->linesCountAfterDeclare) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            sprintf(
                'Expected %d line%s after declare statement, found %d.',
                $this->linesCountAfterDeclare,
                $this->linesCountAfterDeclare === 1 ? '' : 's',
                $linesCountAfter
            ),
            $declarePointer,
            self::CODE_INCORRECT_WHITESPACE_AFTER_DECLARE
        );
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        for ($i = $declareSemicolonPointer + 1; $i < $pointerAfterWhitespaceEnd; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }
        for ($i = 0; $i <= $this->linesCountAfterDeclare; $i++) {
            $phpcsFile->fixer->addNewline($declareSemicolonPointer);
        }
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @return string
     */
    protected function getStrictTypeDeclaration(): string
    {
        return sprintf(
            'strict_types%s=%s1',
            str_repeat(' ', $this->spacesCountAroundEqualsSign),
            str_repeat(' ', $this->spacesCountAroundEqualsSign)
        );
    }

    /**
     * @param mixed $value  Int value to normalize
     * @return int
     */
    protected function normalizeIntValue($value): int
    {
        return (int) trim((string) $value);
    }
}
