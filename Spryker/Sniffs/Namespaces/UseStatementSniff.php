<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use RuntimeException;
use Spryker\Traits\BasicsTrait;

/**
 * "Inline FQCN" must be moved to use statements.
 */
class UseStatementSniff implements Sniff
{
    use BasicsTrait;

    /**
     * @var array
     */
    protected $existingStatements;

    /**
     * @var array
     */
    protected $newStatements = [];

    /**
     * @var array
     */
    protected $allStatements;

    /**
     * @var string|null
     */
    protected $className;

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_NEW, T_FUNCTION, T_DOUBLE_COLON, T_CLASS, T_INTERFACE, T_TRAIT, T_INSTANCEOF, T_CATCH, T_CLOSURE];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $namespaceStatement = $this->getNamespaceStatement($phpcsFile);
        if (!$namespaceStatement) {
            return;
        }

        $this->loadStatements($phpcsFile);

        if ($tokens[$stackPtr]['code'] === T_CLASS || $tokens[$stackPtr]['code'] === T_INTERFACE || $tokens[$stackPtr]['code'] === T_TRAIT) {
            $this->checkUseForClass($phpcsFile, $stackPtr);
        } elseif ($tokens[$stackPtr]['code'] === T_NEW) {
            $this->checkUseForNew($phpcsFile, $stackPtr);
        } elseif ($tokens[$stackPtr]['code'] === T_DOUBLE_COLON) {
            $this->checkUseForStatic($phpcsFile, $stackPtr);
        } elseif ($tokens[$stackPtr]['code'] === T_INSTANCEOF) {
            $this->checkUseForInstanceOf($phpcsFile, $stackPtr);
        } elseif ($tokens[$stackPtr]['code'] === T_CATCH || $tokens[$stackPtr]['code'] === T_CALLABLE) {
            $this->checkUseForCatchOrCallable($phpcsFile, $stackPtr);
        } else {
            $this->checkUseForSignature($phpcsFile, $stackPtr);
            $this->checkUseForReturnTypeHint($phpcsFile, $stackPtr);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkExtends(File $phpcsFile, int $stackPtr): void
    {
        $extendsIndex = $phpcsFile->findNext([T_EXTENDS], $stackPtr + 1);
        if (!$extendsIndex) {
            return;
        }

        $extends = $this->parseExtends($phpcsFile, $extendsIndex);

        foreach ($extends as $extend) {
            $this->fixStatement($phpcsFile, $extend, $stackPtr);
        }
    }

    /**
     * Checks extends, implements.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkUseForClass(File $phpcsFile, int $stackPtr): void
    {
        $this->checkExtends($phpcsFile, $stackPtr);
        $this->checkImplements($phpcsFile, $stackPtr);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkImplements(File $phpcsFile, int $stackPtr): void
    {
        $implementsIndex = $phpcsFile->findNext([T_IMPLEMENTS], $stackPtr + 1);
        if (!$implementsIndex) {
            return;
        }

        $implements = $this->parseImplements($phpcsFile, $implementsIndex);
        foreach ($implements as $implement) {
            $this->fixStatement($phpcsFile, $implement, $stackPtr);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param array $statement
     * @param int $stackPtr
     *
     * @return void
     */
    protected function fixStatement(File $phpcsFile, array $statement, int $stackPtr): void
    {
        if (strpos($statement['content'], '\\') === false) {
            return;
        }

        $partial = strpos($statement['content'], '\\') !== 0;

        $extractedUseStatement = ltrim($statement['content'], '\\');
        $className = substr($statement['content'], strrpos($statement['content'], '\\') + 1);

        $error = 'Use statement ' . $extractedUseStatement . ' for class ' . $className . ' should be in use block.';
        if ($partial) {
            // For now just warn about partial FQCN locally
            //$phpcsFile->addWarning($error, $stackPtr, 'Interface');
            return;
        }

        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'Statement');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();

        $addedUseStatement = $this->addUseStatement($phpcsFile, $className, $extractedUseStatement);

        for ($i = $statement['start']; $i < $statement['end']; ++$i) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        if ($addedUseStatement['alias'] !== null) {
            $phpcsFile->fixer->replaceToken($statement['end'], $addedUseStatement['alias']);
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkUseForNew(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);
        $lastIndex = null;
        $i = $nextIndex;
        $extractedUseStatement = '';
        $lastSeparatorIndex = null;
        while (true) {
            if (!$this->isGivenKind([T_NS_SEPARATOR, T_STRING], $tokens[$i])) {
                break;
            }
            $lastIndex = $i;
            $extractedUseStatement .= $tokens[$i]['content'];

            if ($this->isGivenKind([T_NS_SEPARATOR], $tokens[$i])) {
                $lastSeparatorIndex = $i;
            }
            ++$i;
        }

        if ($lastIndex === null || $lastSeparatorIndex === null) {
            return;
        }

        $extractedUseStatement = ltrim($extractedUseStatement, '\\');

        $className = '';
        for ($i = $lastSeparatorIndex + 1; $i <= $lastIndex; ++$i) {
            $className .= $tokens[$i]['content'];
        }

        $error = 'Use statement ' . $extractedUseStatement . ' for class ' . $className . ' should be in use block.';
        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'New');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();

        $addedUseStatement = $this->addUseStatement($phpcsFile, $className, $extractedUseStatement);

        for ($i = $nextIndex; $i <= $lastSeparatorIndex; ++$i) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        if ($addedUseStatement['alias'] !== null) {
            $phpcsFile->fixer->replaceToken($lastSeparatorIndex + 1, $addedUseStatement['alias']);
            for ($i = $lastSeparatorIndex + 2; $i <= $lastIndex; ++$i) {
                $phpcsFile->fixer->replaceToken($i, '');
            }
        }

        if ($nextIndex === $stackPtr + 1) {
            $phpcsFile->fixer->replaceToken($stackPtr, $tokens[$stackPtr]['content'] . ' ');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkUseForStatic(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $prevIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);

        $lastIndex = null;
        $i = $prevIndex;
        $extractedUseStatement = '';
        $firstSeparatorIndex = null;
        while (true) {
            if (!$this->isGivenKind([T_NS_SEPARATOR, T_STRING], $tokens[$i])) {
                break;
            }
            $lastIndex = $i;
            $extractedUseStatement = $tokens[$i]['content'] . $extractedUseStatement;

            if ($firstSeparatorIndex === null && $this->isGivenKind([T_NS_SEPARATOR], $tokens[$i])) {
                $firstSeparatorIndex = $i;
            }
            --$i;
        }

        if ($lastIndex === null || $firstSeparatorIndex === null) {
            return;
        }

        $extractedUseStatement = ltrim($extractedUseStatement, '\\');

        $className = '';
        for ($i = $firstSeparatorIndex + 1; $i <= $prevIndex; ++$i) {
            $className .= $tokens[$i]['content'];
        }

        $error = 'Use statement ' . $extractedUseStatement . ' for class ' . $className . ' should be in use block.';
        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'Static');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();

        $addedUseStatement = $this->addUseStatement($phpcsFile, $className, $extractedUseStatement);

        for ($i = $lastIndex; $i <= $firstSeparatorIndex; ++$i) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        if ($addedUseStatement['alias'] !== null) {
            $phpcsFile->fixer->replaceToken($firstSeparatorIndex + 1, $addedUseStatement['alias']);
            for ($i = $firstSeparatorIndex + 2; $i <= $lastIndex; ++$i) {
                $phpcsFile->fixer->replaceToken($i, '');
            }
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkUseForInstanceOf(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $classNameIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);

        $lastIndex = null;
        $i = $classNameIndex;
        $extractedUseStatement = '';
        $lastSeparatorIndex = null;
        while (true) {
            if (!$this->isGivenKind([T_NS_SEPARATOR, T_STRING], $tokens[$i])) {
                break;
            }
            $lastIndex = $i;
            $extractedUseStatement .= $tokens[$i]['content'];

            if ($this->isGivenKind([T_NS_SEPARATOR], $tokens[$i])) {
                $lastSeparatorIndex = $i;
            }
            ++$i;
        }

        if ($lastIndex === null || $lastSeparatorIndex === null) {
            return;
        }

        $extractedUseStatement = ltrim($extractedUseStatement, '\\');

        $className = '';
        for ($i = $lastSeparatorIndex + 1; $i <= $lastIndex; ++$i) {
            $className .= $tokens[$i]['content'];
        }

        $error = 'Use statement ' . $extractedUseStatement . ' for class ' . $className . ' should be in use block.';
        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'InstanceOf');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();

        $addedUseStatement = $this->addUseStatement($phpcsFile, $className, $extractedUseStatement);
        $firstSeparatorIndex = $classNameIndex;

        for ($k = $lastSeparatorIndex; $k > $firstSeparatorIndex; --$k) {
            $phpcsFile->fixer->replaceToken($k, '');
        }
        $phpcsFile->fixer->replaceToken($firstSeparatorIndex, '');

        if ($addedUseStatement['alias'] !== null) {
            $phpcsFile->fixer->replaceToken($lastIndex, $addedUseStatement['alias']);
            for ($k = $lastSeparatorIndex + 1; $k < $lastIndex; ++$k) {
                $phpcsFile->fixer->replaceToken($k, '');
            }
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    public function checkUseForCatchOrCallable(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $openParenthesisIndex = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr + 1);
        $closeParenthesisIndex = $tokens[$openParenthesisIndex]['parenthesis_closer'];
        $classNameIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $openParenthesisIndex + 1, null, true);

        $lastIndex = null;
        $i = $classNameIndex;
        $extractedUseStatement = '';
        $lastSeparatorIndex = null;
        while ($i < $closeParenthesisIndex) {
            if (!$this->isGivenKind([T_NS_SEPARATOR, T_STRING], $tokens[$i])) {
                break;
            }
            $lastIndex = $i;
            $extractedUseStatement .= $tokens[$i]['content'];

            if ($this->isGivenKind([T_NS_SEPARATOR], $tokens[$i])) {
                $lastSeparatorIndex = $i;
            }
            ++$i;
        }

        if ($lastIndex === null || $lastSeparatorIndex === null) {
            return;
        }

        $extractedUseStatement = ltrim($extractedUseStatement, '\\');

        $className = '';
        for ($k = $lastSeparatorIndex + 1; $k <= $lastIndex; ++$k) {
            $className .= $tokens[$k]['content'];
        }

        $error = 'Use statement ' . $extractedUseStatement . ' for class ' . $className . ' should be in use block.';
        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'Catch');
        if (!$fix) {
            return;
        }

        $startIndex = $openParenthesisIndex;

        $phpcsFile->fixer->beginChangeset();

        $firstSeparatorIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $startIndex + 1, null, true);

        $addedUseStatement = $this->addUseStatement($phpcsFile, $className, $extractedUseStatement);

        for ($k = $lastSeparatorIndex; $k > $firstSeparatorIndex; --$k) {
            $phpcsFile->fixer->replaceToken($k, '');
        }
        $phpcsFile->fixer->replaceToken($firstSeparatorIndex, '');

        if ($addedUseStatement['alias'] !== null) {
            $phpcsFile->fixer->replaceToken($firstSeparatorIndex + 1, $addedUseStatement['alias']);
            for ($i = $firstSeparatorIndex + 2; $i <= $lastIndex; ++$i) {
                $phpcsFile->fixer->replaceToken($i, '');
            }
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkUseForSignature(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $openParenthesisIndex = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr + 1);
        $closeParenthesisIndex = $tokens[$openParenthesisIndex]['parenthesis_closer'];

        $startIndex = $openParenthesisIndex;
        for ($i = $openParenthesisIndex + 1; $i < $closeParenthesisIndex; $i++) {
            if ($this->isGivenKind(T_COMMA, $tokens[$i])) {
                $startIndex = $i;
            }

            $lastIndex = null;
            $j = $i;
            $extractedUseStatement = '';
            $lastSeparatorIndex = null;
            while (true) {
                if (!$this->isGivenKind([T_NS_SEPARATOR, T_STRING], $tokens[$j])) {
                    break;
                }

                $lastIndex = $j;
                $extractedUseStatement .= $tokens[$j]['content'];
                if ($this->isGivenKind([T_NS_SEPARATOR], $tokens[$j])) {
                    $lastSeparatorIndex = $j;
                }
                ++$j;
            }
            $i = $j;

            if ($lastIndex === null || $lastSeparatorIndex === null) {
                continue;
            }
            $extractedUseStatement = ltrim($extractedUseStatement, '\\');

            $className = '';
            for ($k = $lastSeparatorIndex + 1; $k <= $lastIndex; ++$k) {
                $className .= $tokens[$k]['content'];
            }

            $error = 'Use statement ' . $extractedUseStatement . ' for class ' . $className . ' should be in use block.';
            $fix = $phpcsFile->addFixableError($error, $stackPtr, 'Signature');
            if (!$fix) {
                continue;
            }

            $phpcsFile->fixer->beginChangeset();

            $firstSeparatorIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $startIndex + 1, null, true);

            $addedUseStatement = $this->addUseStatement($phpcsFile, $className, $extractedUseStatement);

            for ($k = $lastSeparatorIndex; $k >= $firstSeparatorIndex; --$k) {
                $phpcsFile->fixer->replaceToken($k, '');
            }

            if ($addedUseStatement['alias'] !== null) {
                $phpcsFile->fixer->replaceToken($lastIndex, $addedUseStatement['alias']);
            }

            $phpcsFile->fixer->endChangeset();
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkUseForReturnTypeHint(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $openParenthesisIndex = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr + 1);
        $closeParenthesisIndex = $tokens[$openParenthesisIndex]['parenthesis_closer'];

        $colonIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $closeParenthesisIndex + 1, null, true);
        if (!$colonIndex) {
            return;
        }

        $startIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $colonIndex + 1, $colonIndex + 3, true);
        if (!$startIndex) {
            return;
        }

        if ($tokens[$startIndex]['type'] === 'T_NULLABLE') {
            $startIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $startIndex + 1, $startIndex + 3, true);
        }

        $lastIndex = null;
        $j = $startIndex;
        $extractedUseStatement = '';
        $lastSeparatorIndex = null;
        while (true) {
            if (!$this->isGivenKind([T_NS_SEPARATOR, T_STRING, T_RETURN_TYPE], $tokens[$j])) {
                break;
            }

            $lastIndex = $j;
            $extractedUseStatement .= $tokens[$j]['content'];
            if ($this->isGivenKind([T_NS_SEPARATOR], $tokens[$j])) {
                $lastSeparatorIndex = $j;
            }
            ++$j;
        }

        if ($lastIndex === null || $lastSeparatorIndex === null) {
            return;
        }

        $extractedUseStatement = ltrim($extractedUseStatement, '\\');
        $className = '';
        for ($k = $lastSeparatorIndex + 1; $k <= $lastIndex; ++$k) {
            $className .= $tokens[$k]['content'];
        }

        $error = 'Use statement ' . $extractedUseStatement . ' for class ' . $className . ' should be in use block.';
        $fix = $phpcsFile->addFixableError($error, $colonIndex, 'ReturnSignature');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();

        $firstSeparatorIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $startIndex, null, true);

        $addedUseStatement = $this->addUseStatement($phpcsFile, $className, $extractedUseStatement);

        for ($k = $lastSeparatorIndex; $k > $firstSeparatorIndex; --$k) {
            $phpcsFile->fixer->replaceToken($k, '');
        }
        $phpcsFile->fixer->replaceToken($firstSeparatorIndex, '');

        if ($addedUseStatement['alias'] !== null) {
            $phpcsFile->fixer->replaceToken($lastIndex, $addedUseStatement['alias']);
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile All the tokens found in the document.
     *
     * @return void
     */
    protected function loadStatements(File $phpcsFile): void
    {
        $this->className = $this->findClassName($phpcsFile);

        if ($this->existingStatements !== null) {
            return;
        }

        $existingStatements = $this->getUseStatements($phpcsFile);
        $this->existingStatements = $existingStatements;
        $this->allStatements = $existingStatements;
        $this->newStatements = [];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     *
     * @return bool
     */
    protected function isBlacklistedFile(File $phpcsFile): bool
    {
        $file = $phpcsFile->getFilename();
        if (strpos($file, DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR) !== false) {
            return true;
        }

        return false;
    }

    /**
     * Another sniff takes care of that, we just ignore then.
     *
     * @param string $statementContent
     *
     * @return bool
     */
    protected function isMultipleUseStatement(string $statementContent): bool
    {
        if (strpos($statementContent, ',') !== false) {
            return true;
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param string $shortName
     * @param string $fullName
     *
     * @return string|null
     */
    protected function generateUniqueAlias(File $phpcsFile, string $shortName, string $fullName): ?string
    {
        $alias = $shortName;
        $count = 0;
        $pieces = explode('\\', $fullName);
        if ($this->isSameVendor($phpcsFile, $fullName)) {
            $pieces = array_reverse($pieces);
            array_shift($pieces);
        }

        // To avoid collisions with PHP core classes we try to add this prefix for all root namespaced classes
        if (count($pieces) < 1) {
            array_unshift($pieces, 'Php');
        }

        while (isset($this->allStatements[$alias]) || $this->className && $alias === $this->className) {
            $alias = $shortName;

            if (count($pieces) - 1 < $count) {
                return null;
            }

            $prefix = '';
            for ($i = 0; $i <= $count; ++$i) {
                $prefix .= $pieces[$i];
            }

            $alias = $prefix . $alias;

            $count++;
        }

        return $alias;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param string $fullName
     *
     * @return bool
     */
    protected function isSameVendor(File $phpcsFile, string $fullName): bool
    {
        $namespaceStatement = $this->getNamespaceStatement($phpcsFile);
        $firstSeparator = strpos($namespaceStatement['namespace'], '\\');
        if ($firstSeparator === false) {
            return $namespaceStatement['namespace'] === $fullName;
        }

        return strpos($namespaceStatement['namespace'], substr($fullName, 0, $firstSeparator)) === 0;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     *
     * @return array
     */
    protected function getUseStatements(File $phpcsFile): array
    {
        $tokens = $phpcsFile->getTokens();

        $statements = [];
        foreach ($tokens as $index => $token) {
            if ($token['code'] !== T_USE || $token['level'] > 0) {
                continue;
            }

            $useStatementStartIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $index + 1, null, true);

            // Ignore function () use ($foo) {}
            if ($tokens[$useStatementStartIndex]['content'] === '(') {
                continue;
            }

            $semicolonIndex = $phpcsFile->findNext(T_SEMICOLON, $useStatementStartIndex + 1);
            $useStatementEndIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $semicolonIndex - 1, null, true);

            $statement = '';
            for ($i = $useStatementStartIndex; $i <= $useStatementEndIndex; $i++) {
                $statement .= $tokens[$i]['content'];
            }

            if ($this->isMultipleUseStatement($statement)) {
                continue;
            }

            $statementParts = preg_split('/\s+as\s+/i', $statement);

            if (count($statementParts) === 1) {
                $fullName = $statement;
                $statementParts = explode('\\', $fullName);
                $shortName = end($statementParts);
                $alias = null;
            } else {
                $fullName = $statementParts[0];
                $alias = $statementParts[1];
                $statementParts = explode('\\', $fullName);
                $shortName = end($statementParts);
            }

            $shortName = trim($shortName);
            $fullName = trim($fullName);
            $key = $alias ?: $shortName;

            $statements[$key] = [
                'alias' => $alias,
                'end' => $semicolonIndex,
                'fullName' => ltrim($fullName, '\\'),
                'shortName' => $shortName,
                'start' => $index,
            ];
        }

        return $statements;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param string $shortName
     * @param string $fullName
     *
     * @throws \RuntimeException
     *
     * @return array
     */
    protected function addUseStatement(File $phpcsFile, string $shortName, string $fullName): array
    {
        foreach ($this->allStatements as $useStatement) {
            if ($useStatement['fullName'] === $fullName) {
                return $useStatement;
            }
        }

        $alias = $this->generateUniqueAlias($phpcsFile, $shortName, $fullName);
        if (!$alias) {
            throw new RuntimeException(sprintf('Could not generate unique alias for %s (%s).', $shortName, $fullName));
        }

        $result = [
            'alias' => $alias === $shortName ? null : $alias,
            'fullName' => $fullName,
            'shortName' => $shortName,
        ];
        $this->insertUseStatement($phpcsFile, $result);

        $this->allStatements[$alias] = $result;
        $this->newStatements[$alias] = $result;

        return $result;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param array $useStatement
     *
     * @return void
     */
    protected function insertUseStatement(File $phpcsFile, array $useStatement): void
    {
        $existingStatements = $this->existingStatements;
        if ($existingStatements) {
            $lastOne = array_pop($existingStatements);

            $lastUseStatementIndex = $lastOne['end'];
        } else {
            $namespaceStatement = $this->getNamespaceStatement($phpcsFile);

            $lastUseStatementIndex = $namespaceStatement['end'];
        }

        $phpcsFile->fixer->addNewline($lastUseStatementIndex);
        $phpcsFile->fixer->addContent($lastUseStatementIndex, $this->generateUseStatement($useStatement));
    }

    /**
     * @param array $useStatement
     *
     * @return string
     */
    protected function generateUseStatement(array $useStatement): string
    {
        $alias = '';
        if (!empty($useStatement['alias'])) {
            $alias = ' as ' . $useStatement['alias'];
        }

        $content = 'use ' . $useStatement['fullName'] . $alias . ';';

        return $content;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     *
     * @return string|null
     */
    protected function findClassName(File $phpcsFile): ?string
    {
        $index = $phpcsFile->findNext([T_CLASS, T_INTERFACE, T_TRAIT], 0);
        if (!$index) {
            return null;
        }

        $tokens = $phpcsFile->getTokens();

        $nextIndex = $phpcsFile->findNext(T_WHITESPACE, $index + 1, null, true);
        $className = $tokens[$nextIndex]['content'];

        return $className;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $extendsStartIndex
     *
     * @return array
     */
    protected function parseExtends(File $phpcsFile, int $extendsStartIndex): array
    {
        $extendsEndIndex = $phpcsFile->findNext([T_IMPLEMENTS, T_OPEN_CURLY_BRACKET], $extendsStartIndex + 1);

        return $this->parse($phpcsFile, $extendsStartIndex, $extendsEndIndex);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $implementsStartIndex
     *
     * @return array
     */
    protected function parseImplements(File $phpcsFile, int $implementsStartIndex): array
    {
        $implementsEndIndex = $phpcsFile->findNext(T_OPEN_CURLY_BRACKET, $implementsStartIndex + 1);

        return $this->parse($phpcsFile, $implementsStartIndex, $implementsEndIndex);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $startIndex
     * @param int $endIndex
     *
     * @throws \RuntimeException
     *
     * @return array
     */
    protected function parse(File $phpcsFile, int $startIndex, int $endIndex): array
    {
        $tokens = $phpcsFile->getTokens();
        if (empty($tokens[$endIndex])) {
            throw new RuntimeException('Should not happen');
        }

        $classIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $startIndex + 1, null, true);
        if (empty($tokens[$classIndex])) {
            throw new RuntimeException('Should not happen');
        }

        $implements = [];

        $i = $classIndex;
        while ($i < $endIndex) {
            if (empty($tokens[$i])) {
                break;
            }

            while ($tokens[$i]['code'] !== T_NS_SEPARATOR && $tokens[$i]['code'] !== T_STRING) {
                $i++;
                if (empty($tokens[$i])) {
                    break;
                }

                continue;
            }

            if (empty($tokens[$i])) {
                break;
            }

            $current = $i;
            $className = $tokens[$i]['content'];
            $i++;

            if (empty($tokens[$i])) {
                break;
            }

            while ($tokens[$i]['code'] === T_NS_SEPARATOR || $tokens[$i]['code'] === T_STRING) {
                $className .= $tokens[$i]['content'];
                $i++;

                if (empty($tokens[$i])) {
                    break;
                }
            }

            $implements[] = [
                'start' => $current,
                'end' => $i - 1,
                'content' => $className,
            ];
        }

        return $implements;
    }
}
