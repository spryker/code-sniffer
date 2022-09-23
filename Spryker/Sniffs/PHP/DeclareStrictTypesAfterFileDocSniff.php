<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\TokenHelper;

class DeclareStrictTypesAfterFileDocSniff implements Sniff
{
    /**
     * @var string
     */
    public const CODE_DECLARE_STRICT_TYPES_WRONG_POSITION = 'DeclareStrictTypesWrongPosition';

    /**
     * @var string
     */
    public const CODE_DECLARE_STRICT_TYPES_MISSING = 'DeclareStrictTypesMissing';

    /**
     * @var array<int|string>
     */
    protected const ALLOWED_TOKEN_CODES_BEFORE_FILE_DOC = [
        T_WHITESPACE, T_DECLARE, T_OPEN_PARENTHESIS, T_STRING, T_EQUAL, T_LNUMBER, T_CLOSE_PARENTHESIS, T_SEMICOLON,
    ];

    /**
     * @var bool|null
     */
    public $strictTypesMandatory;

    /**
     * @var int
     */
    public $linesCountBeforeDeclare = 1;

    /**
     * @var int
     */
    public $linesCountAfterDeclare = 1;

    /**
     * @var int
     */
    public $spacesCountAroundEqualsSign = 0;

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
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $this->onBeforeProcess();

        if (TokenHelper::findPrevious($phpcsFile, T_DOC_COMMENT_CLOSE_TAG, $stackPtr - 1) !== null) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        // Don't do any changes in scripts file
        if ($this->isScript($tokens)) {
            return;
        }

        $declareStrictTypeTokenPosition = $this->getStrictTypeDeclareTokenPosition($tokens);
        $strictTypesMandatoryInFile = $this->strictTypesMandatory;

        // Don't do anything if file doesn't contain strict_types declaration
        // and strict_types declaration is not mandatory
        if (!$declareStrictTypeTokenPosition) {
            if (!$strictTypesMandatoryInFile) {
                return;
            }

            $fix = $phpcsFile->addFixableError(
                'declare(strict_types=1) is not found',
                $stackPtr,
                static::CODE_DECLARE_STRICT_TYPES_MISSING,
            );

            if (!$fix) {
                return;
            }
        }

        // Don't do anything if we are after the wrong documentation block
        if (!$this->isFileDocumentation($tokens, $stackPtr)) {
            return;
        }

        // Remove strict_types declaration if it is after open tag
        if ($declareStrictTypeTokenPosition && $this->isDeclareAfterOpenTag($tokens, $declareStrictTypeTokenPosition)) {
            // If file contains strict_types declaration, it is mandatory to keep it in the file
            $strictTypesMandatoryInFile = true;

            $fix = $phpcsFile->addFixableError(
                'declare(strict_types=1) is after PHP open tag, but it should be after file doc',
                $declareStrictTypeTokenPosition,
                static::CODE_DECLARE_STRICT_TYPES_WRONG_POSITION,
            );

            if ($fix) {
                $this->removeStrictTypeDeclaration($phpcsFile, $tokens, $declareStrictTypeTokenPosition);
            }
        }

        // Add strict_types declaration after file documentation

        $declarePointer = TokenHelper::findNextEffective($phpcsFile, $stackPtr + 1);

        if ($strictTypesMandatoryInFile && ($declarePointer === null || $tokens[$declarePointer]['code'] !== T_DECLARE)) {
            $fix = $phpcsFile->addFixableError(
                sprintf('Missing declare(%s) after file doc.', $this->getStrictTypeDeclaration()),
                $stackPtr,
                static::CODE_DECLARE_STRICT_TYPES_MISSING,
            );
            if ($fix) {
                $this->addStrictTypesDeclaration($phpcsFile, (int)$declarePointer, $stackPtr);
            }
        }
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
     * @param array<int, array<string, mixed>> $tokens
     *
     * @return bool
     */
    protected function isScript(array $tokens): bool
    {
        for ($i = 0, $max = count($tokens); $i < $max; ++$i) {
            $tokenCode = $tokens[$i]['code'];
            if (in_array($tokenCode, [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, array<string, mixed>> $tokens
     * @param int $declarePosition
     *
     * @return bool
     */
    protected function isDeclareAfterOpenTag(array $tokens, int $declarePosition): bool
    {
        for ($i = $declarePosition - 1; $i >= 0; --$i) {
            if ($tokens[$i]['code'] === T_OPEN_TAG) {
                return true;
            }
            if ($tokens[$i]['code'] === T_WHITESPACE) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * @param array<int, array<string, mixed>> $tokens
     * @param int $stackPtr
     *
     * @return bool
     */
    protected function isFileDocumentation(array $tokens, int $stackPtr): bool
    {
        $nextNonEmptyTokenFound = false;
        $nextIdx = $stackPtr;

        do {
            $tokenCode = $tokens[++$nextIdx]['code'];
            if (in_array($tokenCode, [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM])) {
                return false;
            }
            if ($tokenCode !== T_WHITESPACE) {
                $nextNonEmptyTokenFound = true;
            }
        } while (!$nextNonEmptyTokenFound);

        $docTokenPrefix = 'T_DOC_COMMENT_';
        for ($i = $stackPtr - 1; $i >= 0; --$i) {
            $tokenCode = $tokens[$i]['code'];
            if ($tokenCode === T_OPEN_TAG) {
                return true;
            }
            if (
                substr($tokens[$i]['type'], 0, strlen($docTokenPrefix)) === $docTokenPrefix
                || in_array($tokenCode, static::ALLOWED_TOKEN_CODES_BEFORE_FILE_DOC)
            ) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param array<int, array<string, mixed>> $tokens
     * @param int $declareStrictTypeTokenPosition
     *
     * @return void
     */
    protected function removeStrictTypeDeclaration(File $phpcsFile, array $tokens, int $declareStrictTypeTokenPosition): void
    {
        $phpcsFile->fixer->beginChangeset();

        $strictTypeOpenerPosition = $tokens[$declareStrictTypeTokenPosition]['parenthesis_opener'];
        $strictTypeCloserPosition = $tokens[$declareStrictTypeTokenPosition]['parenthesis_closer'];

        // Remove all tokens that relate to declare strict_types
        for ($i = $strictTypeOpenerPosition - 1; $i <= $strictTypeCloserPosition + 1; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        // Remove all empty lines before declare strict_types
        $whitespace = true;
        $i = $strictTypeOpenerPosition - 1;
        do {
            $phpcsFile->fixer->replaceToken($i, '');
            --$i;
            if ($tokens[$i]['code'] !== T_WHITESPACE) {
                $whitespace = false;
            }
        } while ($whitespace);

        // Remove all empty lines after declare strict_types
        $whitespace = true;
        $i = $strictTypeCloserPosition + 1;
        do {
            $phpcsFile->fixer->replaceToken($i, '');
            ++$i;
            if ($tokens[$i]['code'] !== T_WHITESPACE) {
                $whitespace = false;
            }
        } while ($whitespace);

        $phpcsFile->fixer->addNewline($i - 1);
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $declarePointer
     * @param int $stackPtr
     *
     * @return void
     */
    protected function addStrictTypesDeclaration(File $phpcsFile, int $declarePointer, int $stackPtr): void
    {
        $phpcsFile->fixer->beginChangeset();

        $linesCountBefore = $declarePointer - $stackPtr - 2;
        if ($linesCountBefore <= $this->linesCountBeforeDeclare) {
            for ($i = 0; $i < $this->linesCountBeforeDeclare - $linesCountBefore; ++$i) {
                $phpcsFile->fixer->addNewline($declarePointer - 1);
            }
        }

        $phpcsFile->fixer->addContent(
            $declarePointer - 1,
            sprintf('declare(%s);%s', $this->getStrictTypeDeclaration(), $phpcsFile->eolChar),
        );

        if ($this->linesCountAfterDeclare > 0) {
            for ($i = 0; $i < $this->linesCountAfterDeclare; ++$i) {
                $phpcsFile->fixer->addNewline($declarePointer - 1);
            }
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param array<int, array<string, mixed>> $tokens
     *
     * @return int|null
     */
    protected function getStrictTypeDeclareTokenPosition(array $tokens): ?int
    {
        for ($i = 0, $max = count($tokens); $i < $max; ++$i) {
            $token = $tokens[$i];
            if ($token['code'] === T_CLASS) {
                return null;
            }
            if ($token['code'] === T_DECLARE) {
                $declareOpenIdx = $token['parenthesis_opener'] + 1;
                if ($tokens[$declareOpenIdx]['code'] === T_STRING && $tokens[$declareOpenIdx]['content'] === 'strict_types') {
                    return $i;
                }
            }
        }

        return null;
    }

    /**
     * @return string
     */
    protected function getStrictTypeDeclaration(): string
    {
        return sprintf(
            'strict_types%s=%s1',
            str_repeat(' ', $this->spacesCountAroundEqualsSign),
            str_repeat(' ', $this->spacesCountAroundEqualsSign),
        );
    }

    /**
     * @param mixed $value Int value to normalize
     *
     * @return int
     */
    protected function normalizeIntValue($value): int
    {
        return (int)trim((string)$value);
    }
}
