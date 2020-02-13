<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\AbstractSniffs;

use PHP_CodeSniffer\Exceptions\DeepExitException;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\ClassHelper;
use SlevomatCodingStandard\Helpers\DocCommentHelper;
use SlevomatCodingStandard\Helpers\EmptyFileException;
use SlevomatCodingStandard\Helpers\NamespaceHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use Spryker\Traits\BasicsTrait;

abstract class AbstractSprykerSniff implements Sniff
{
    use BasicsTrait;

    protected const NAMESPACE_SPRYKER = 'Spryker';

    /**
     * @var string[] These markers must remain as inline comments
     */
    protected static $phpStormMarkers = [
        '@noinspection',
    ];

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPtr
     *
     * @return bool
     */
    protected function isPhpStormMarker(File $phpCsFile, int $stackPtr): bool
    {
        $tokens = $phpCsFile->getTokens();
        $line = $tokens[$stackPtr]['line'];
        if ($tokens[$stackPtr]['type'] !== 'T_DOC_COMMENT_OPEN_TAG') {
            return false;
        }
        $end = $tokens[$stackPtr]['comment_closer'] - 1;
        if ($line !== $tokens[$end]['line']) {
            return false; // Not an inline comment
        }
        foreach (static::$phpStormMarkers as $marker) {
            if ($phpCsFile->findNext(T_DOC_COMMENT_TAG, $stackPtr + 1, $end, false, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isSprykerNamespace(File $phpCsFile): bool
    {
        $namespace = $this->getNamespace($phpCsFile);

        return strpos($namespace, static::NAMESPACE_SPRYKER) === 0;
    }

    /**
     * Get level of indentation, 0 based.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $index
     *
     * @return int
     */
    protected function getIndentationLevel(File $phpcsFile, int $index): int
    {
        $tokens = $phpcsFile->getTokens();

        $whitespace = $this->getIndentationWhitespace($phpcsFile, $index);
        $char = $this->getIndentationCharacter($whitespace);

        $level = $tokens[$index]['column'] - 1;

        if ($char === "\t") {
            return $level;
        }

        return (int)($level / 4);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getNamespace(File $phpCsFile): string
    {
        $className = $this->getClassName($phpCsFile);
        $classNameParts = explode('\\', $className);

        return $classNameParts[0];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getModule(File $phpCsFile): string
    {
        $className = $this->getClassName($phpCsFile);
        $classNameParts = explode('\\', $className);

        if (count($classNameParts) < 3) {
            return '';
        }

        return $classNameParts[2];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getLayer(File $phpCsFile): string
    {
        $className = $this->getClassName($phpCsFile);
        $classNameParts = explode('\\', $className);

        if (count($classNameParts) < 4) {
            return '';
        }

        return $classNameParts[3];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string|null
     */
    protected function getClassNameWithNamespace(File $phpCsFile): ?string
    {
        try {
            $lastToken = TokenHelper::getLastTokenPointer($phpCsFile);
        } catch (EmptyFileException $e) {
            return null;
        }

        if (!NamespaceHelper::findCurrentNamespaceName($phpCsFile, $lastToken)) {
            return null;
        }

        return ClassHelper::getFullyQualifiedName(
            $phpCsFile,
            $phpCsFile->findPrevious(TokenHelper::$typeKeywordTokenCodes, $lastToken)
        );
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getClassName(File $phpCsFile): string
    {
        $namespace = $this->getClassNameWithNamespace($phpCsFile);

        if ($namespace) {
            return trim($namespace, '\\');
        }

        $fileName = $phpCsFile->getFilename();
        $fileNameParts = explode(DIRECTORY_SEPARATOR, $fileName);
        $directoryPosition = array_search('src', array_values($fileNameParts), true);
        if (!$directoryPosition) {
            $directoryPosition = array_search('tests', array_values($fileNameParts), true) + 1;
        }
        $classNameParts = array_slice($fileNameParts, $directoryPosition + 1);
        $className = implode('\\', $classNameParts);
        $className = str_replace('.php', '', $className);

        return $className;
    }

    /**
     * Checks if the given token scope contains a single or multiple token codes/types.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param string|array $search
     * @param int $start
     * @param int $end
     * @param bool $skipNested
     *
     * @return bool
     */
    protected function contains(File $phpcsFile, $search, int $start, int $end, bool $skipNested = true): bool
    {
        $tokens = $phpcsFile->getTokens();

        for ($i = $start; $i <= $end; $i++) {
            if ($skipNested && $tokens[$i]['code'] === T_OPEN_PARENTHESIS) {
                $i = $tokens[$i]['parenthesis_closer'];

                continue;
            }
            if ($skipNested && $tokens[$i]['code'] === T_OPEN_SHORT_ARRAY) {
                $i = $tokens[$i]['bracket_closer'];

                continue;
            }
            if ($skipNested && $tokens[$i]['code'] === T_OPEN_CURLY_BRACKET) {
                $i = $tokens[$i]['bracket_closer'];

                continue;
            }

            if ($this->isGivenKind($search, $tokens[$i])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the given token scope requires brackets when used standalone.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $openingBraceIndex
     * @param int $closingBraceIndex
     *
     * @return bool
     */
    protected function needsBrackets(File $phpcsFile, int $openingBraceIndex, int $closingBraceIndex): bool
    {
        $tokens = $phpcsFile->getTokens();

        $whitelistedCodes = [
            T_LNUMBER,
            T_STRING,
            T_BOOL_CAST,
            T_STRING_CAST,
            T_INT_CAST,
            T_ARRAY_CAST,
            T_COMMENT,
            T_WHITESPACE,
            T_VARIABLE,
            T_DOUBLE_COLON,
            T_OBJECT_OPERATOR,
        ];

        for ($i = $openingBraceIndex + 1; $i < $closingBraceIndex; $i++) {
            if ($tokens[$i]['type'] === 'T_OPEN_PARENTHESIS') {
                $i = $tokens[$i]['parenthesis_closer'];

                continue;
            }
            if (in_array($tokens[$i]['code'], $whitelistedCodes)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return int|null Stackpointer value of docblock end tag, or null if cannot be found
     */
    protected function findRelatedDocBlock(File $phpCsFile, int $stackPointer): ?int
    {
        $tokens = $phpCsFile->getTokens();

        $line = $tokens[$stackPointer]['line'];
        $beginningOfLine = $stackPointer;
        while (!empty($tokens[$beginningOfLine - 1]) && $tokens[$beginningOfLine - 1]['line'] === $line) {
            $beginningOfLine--;
        }

        if (!empty($tokens[$beginningOfLine - 2]) && $tokens[$beginningOfLine - 2]['type'] === 'T_DOC_COMMENT_CLOSE_TAG') {
            return $beginningOfLine - 2;
        }

        if (!empty($tokens[$beginningOfLine - 3]) && $tokens[$beginningOfLine - 3]['type'] === 'T_DOC_COMMENT_CLOSE_TAG') {
            return $beginningOfLine - 3;
        }

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $index
     * @param int $count
     *
     * @return void
     */
    protected function outdent(File $phpcsFile, int $index, int $count = 1): void
    {
        $tokens = $phpcsFile->getTokens();
        $char = $this->getIndentationCharacter($tokens[$index]['content'], true);

        $phpcsFile->fixer->replaceToken($index, $this->strReplaceOnce($char, '', $tokens[$index]['content']));
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $index
     * @param int $count
     *
     * @return void
     */
    protected function indent(File $phpcsFile, int $index, int $count = 1): void
    {
        $tokens = $phpcsFile->getTokens();

        $phpcsFile->fixer->replaceToken($index, $this->strReplaceOnce("\t", "\t\t", $tokens[$index]['content']));
    }

    /**
     * @param string $search
     * @param string $replace
     * @param string $subject
     *
     * @return string
     */
    protected function strReplaceOnce(string $search, string $replace, string $subject): string
    {
        $pos = strpos($subject, $search);
        if ($pos === false) {
            return $subject;
        }

        return substr($subject, 0, $pos) . $replace . substr($subject, $pos + strlen($search));
    }

    /**
     * @param string $content
     * @param bool $correctLength
     *
     * @return string
     */
    protected function getIndentationCharacter(string $content, bool $correctLength = false): string
    {
        if (strpos($content, "\n")) {
            $parts = explode("\n", $content);
            array_shift($parts);
        } else {
            $parts = (array)$content;
        }

        $char = "\t";
        $countTabs = $countSpaces = 0;
        foreach ($parts as $part) {
            $countTabs += substr_count($part, $char);
            $countSpaces += (int)(substr_count($part, ' ') / 4);
        }

        if ($countSpaces > $countTabs) {
            $char = $correctLength ? '    ' : ' ';
        }

        return $char;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $prevIndex
     *
     * @return string
     */
    protected function getIndentationWhitespace(File $phpcsFile, int $prevIndex): string
    {
        $tokens = $phpcsFile->getTokens();

        $firstIndex = $this->getFirstTokenOfLine($tokens, $prevIndex);
        $whitespace = '';
        if ($tokens[$firstIndex]['type'] === 'T_WHITESPACE') {
            $whitespace = $tokens[$firstIndex]['content'];
        }

        return $whitespace;
    }

    /**
     * @param array $tokens
     * @param int $index
     *
     * @return int
     */
    protected function getFirstTokenOfLine(array $tokens, int $index): int
    {
        $line = $tokens[$index]['line'];

        $currentIndex = $index;
        while ($tokens[$currentIndex - 1]['line'] === $line) {
            $currentIndex--;
        }

        return $currentIndex;
    }

    /**
     * @param array $tokens
     * @param int $index
     *
     * @return int
     */
    protected function getLastTokenOfLine(array $tokens, int $index): int
    {
        $line = $tokens[$index]['line'];

        $currentIndex = $index;
        while ($tokens[$currentIndex + 1]['line'] === $line) {
            $currentIndex++;
        }

        return $currentIndex;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param array $tokens
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isMarkedAsDeprecated(File $phpCsFile, array $tokens, int $stackPointer): bool
    {
        $begin = $tokens[$stackPointer]['scope_opener'] + 1;
        $end = $tokens[$stackPointer]['scope_closer'] - 1;
        for ($i = $begin; $i <= $end; $i++) {
            $token = $tokens[$i];
            if ($token['code'] === T_CONSTANT_ENCAPSED_STRING) {
                if (strpos(strtolower($token['content']), 'deprecated') !== false) {
                    return true;
                }
            }
        }

        if ($this->isMarkedDeprecatedInDocBlock($phpCsFile, $tokens, $stackPointer)) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param array $tokens
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isMarkedDeprecatedInDocBlock(File $phpCsFile, array $tokens, int $stackPointer): bool
    {
        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);
        if (!$docBlockEndIndex) {
            return false;
        }
        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];
        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if (!in_array($tokens[$i]['content'], ['@deprecated'], true)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return string[]
     */
    protected function getDocBlockReturnTypes(File $phpCsFile, int $stackPointer): array
    {
        $docBlock = DocCommentHelper::getDocComment($phpCsFile, $stackPointer);

        if ($docBlock === null) {
            return [];
        }

        preg_match('/(@return\s+)(\S+)/', $docBlock, $matches);

        if (!$matches) {
            return [];
        }

        $returnTypes = array_pop($matches);
        $returnTypes = trim($returnTypes);
        $returnTypes = explode('|', $returnTypes);

        return $returnTypes;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $fileDocBlockStartPosition
     *
     * @return void
     */
    protected function assertNewlineBefore(File $phpCsFile, int $fileDocBlockStartPosition): void
    {
        $tokens = $phpCsFile->getTokens();

        $prevIndex = $phpCsFile->findPrevious(T_WHITESPACE, $fileDocBlockStartPosition - 1, 0, true);

        if ($tokens[$prevIndex]['line'] === $tokens[$fileDocBlockStartPosition]['line'] - 2) {
            return;
        }

        $fix = $phpCsFile->addFixableError('Whitespace issue around file doc block', $fileDocBlockStartPosition, 'FileDocBlockSpacing');
        if (!$fix) {
            return;
        }

        $phpCsFile->fixer->beginChangeset();

        if ($tokens[$prevIndex]['line'] > $tokens[$fileDocBlockStartPosition]['line'] - 2) {
            $phpCsFile->fixer->addNewline($prevIndex);
        } else {
            $index = $prevIndex;
            while ($index < $fileDocBlockStartPosition - 1) {
                $index++;
                $phpCsFile->fixer->replaceToken($index, '');
            }
        }

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @throws \PHP_CodeSniffer\Exceptions\DeepExitException
     *
     * @return int
     */
    protected function getMethodSignatureLength(File $phpcsFile, int $stackPtr): int
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['code'] !== T_FUNCTION) {
            throw new DeepExitException('This can only be run on a method signature.');
        }
        $openParenthesisPosition = $tokens[$stackPtr]['parenthesis_opener'];
        $closeParenthesisPosition = $tokens[$stackPtr]['parenthesis_closer'];

        $methodProperties = $phpcsFile->getMethodProperties($stackPtr);
        $methodParameters = $phpcsFile->getMethodParameters($stackPtr);
        if ($this->areTokensOnTheSameLine($tokens, $openParenthesisPosition, $closeParenthesisPosition)) {
            return $this->getMethodSingleLineSignatureLength($tokens, $stackPtr);
        }

        return $this->getMethodSignatureMultilineLength($tokens, $stackPtr, $methodProperties, $methodParameters);
    }

    /**
     * @param array $tokens
     * @param int $firstPosition
     * @param int $secondPosition
     *
     * @return bool
     */
    protected function areTokensOnTheSameLine(array $tokens, int $firstPosition, int $secondPosition): bool
    {
        return $tokens[$firstPosition]['line'] === $tokens[$secondPosition]['line'];
    }

    /**
     * @param array $tokens
     * @param int $stackPtr
     *
     * @return int
     */
    protected function getMethodSingleLineSignatureLength(array $tokens, int $stackPtr): int
    {
        $position = $this->getLineEndingPosition($tokens, $stackPtr);

        return $tokens[$position]['column'] - 1;
    }

    /**
     * @param array $tokens
     * @param int $position
     *
     * @return int
     */
    protected function getLineEndingPosition(array $tokens, int $position): int
    {
        while (!empty($tokens[$position]) && strpos($tokens[$position]['content'], PHP_EOL) === false) {
            $position++;
        }

        return $position;
    }

    /**
     * @param array $tokens
     * @param int $stackPtr
     * @param array $methodProperties
     * @param array $methodParameters
     *
     * @return int
     */
    protected function getMethodSignatureMultilineLength(
        array $tokens,
        int $stackPtr,
        array $methodProperties,
        array $methodParameters
    ): int {
        $totalLength = $this->getMethodSingleLineSignatureLength($tokens, $stackPtr);
        $firstLineEndPosition = $this->getLineEndingPosition($tokens, $stackPtr);
        foreach ($methodParameters as $parameter) {
            if ($tokens[$parameter['token']]['line'] === $tokens[$stackPtr]['line']) {
                //the parameters are on the first line of the signature.
                if ($tokens[$firstLineEndPosition - 1]['code'] === T_COMMA) {
                    //space after comma.
                    $totalLength++;
                }

                continue;
            }
            $totalLength += $this->getParameterTotalLength($parameter);
            if ($parameter['comma_token'] !== false) {
                //comma + space
                $totalLength += 2;
            }
        }
        //closing parenthesis
        $totalLength++;
        // column (:) and space before the returnType
        $totalLength += mb_strlen($methodProperties['return_type']) + 2;

        return $totalLength;
    }

    /**
     * @param array $methodParameter
     *
     * @return int
     */
    protected function getParameterTotalLength(array $methodParameter): int
    {
        $length = 0;
        $length += mb_strlen($methodParameter['content']);

        return $length;
    }
}
