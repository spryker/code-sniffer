<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * All doc blocks must use FQCN for class names.
 */
class FullyQualifiedClassNameInDocBlockSniff implements Sniff
{
    /**
     * @var string[]
     */
    public static $whitelistedTypes = [
        'string', 'int', 'integer', 'float', 'bool', 'boolean', 'resource', 'null', 'void', 'callable',
        'array', 'iterable', 'mixed', 'object', 'false', 'true', 'self', 'static', '$this',
    ];

    /**
     * @var string[]
     */
    public static $whitelistedStartsWithTypes = ['array<', 'iterable<', 'array{'];

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_CLASS,
            T_INTERFACE,
            T_TRAIT,
            T_FUNCTION,
            T_VARIABLE,
            T_COMMENT,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);

        if (!$docBlockEndIndex) {
            return;
        }

        $tokens = $phpCsFile->getTokens();

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if (!in_array($tokens[$i]['content'], ['@return', '@param', '@throws', '@var', '@method', '@property'], true)) {
                continue;
            }

            $classNameIndex = $i + 2;

            if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
                continue;
            }

            $content = $tokens[$classNameIndex]['content'];
            $appendix = '';

            $variablePos = strpos($content, ' $');
            if ($variablePos !== false) {
                $appendix = substr($content, $variablePos);
                $content = substr($content, 0, $variablePos);
            }

            preg_match('#(.+<[^>]+>)#', $content, $matches);
            if ($matches) {
                $appendix = substr($content, strlen($matches[1])) . $appendix;
                $content = $matches[1];
            } else {
                $spaceIndex = strpos($content, ' ');
                if ($spaceIndex) {
                    $appendix = substr($content, $spaceIndex) . $appendix;
                    $content = substr($content, 0, $spaceIndex);
                }
            }

            if (!$content) {
                continue;
            }

            $types = $this->parseTypes($content);

            $this->fixClassNames($phpCsFile, $classNameIndex, $types, $appendix);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $classNameIndex
     * @param string[] $classNames
     * @param string $appendix
     *
     * @return void
     */
    protected function fixClassNames(File $phpCsFile, int $classNameIndex, array $classNames, string $appendix): void
    {
        $classNameMap = $this->generateClassNameMap($phpCsFile, $classNameIndex, $classNames);
        if (!$classNameMap) {
            return;
        }

        $message = [];
        foreach ($classNameMap as $className => $useStatement) {
            $message[] = $className . ' => ' . $useStatement;
        }

        $fix = $phpCsFile->addFixableError(implode(', ', $message), $classNameIndex, 'FQCN');
        if ($fix) {
            $newContent = implode('|', $classNames);

            $phpCsFile->fixer->replaceToken($classNameIndex, $newContent . $appendix);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $classNameIndex
     * @param string[] $classNames
     *
     * @return string[]
     */
    protected function generateClassNameMap(File $phpCsFile, int $classNameIndex, array &$classNames): array
    {
        $result = [];

        foreach ($classNames as $key => $className) {
            foreach (static::$whitelistedStartsWithTypes as $whitelistedStartsWithType) {
                if (strpos($className, $whitelistedStartsWithType) === 0) {
                    // We skip for now
                    continue 2;
                }
            }

            $arrayOfObject = 0;
            while (substr($className, -2) === '[]') {
                $arrayOfObject++;
                $className = substr($className, 0, -2);
            }

            if (preg_match('#^\((.+)\)#', $className, $matches)) {
                $subClassNames = explode('|', $matches[1]);
                $newClassName = '(' . $this->generateClassNameMapForUnionType($phpCsFile, $classNameIndex, $className, $subClassNames) . ')';
                if ($newClassName === $className) {
                    continue;
                }

                $classNames[$key] = $newClassName . ($arrayOfObject ? str_repeat('[]', $arrayOfObject) : '');
                $result[$className . ($arrayOfObject ? str_repeat('[]', $arrayOfObject) : '')] = $classNames[$key];

                continue;
            }

            if (strpos($className, '\\') !== false) {
                continue;
            }

            if (in_array($className, static::$whitelistedTypes, true)) {
                continue;
            }
            $useStatement = $this->findUseStatementForClassName($phpCsFile, $className);
            if (!$useStatement) {
                $message = 'Invalid typehint `%s`';
                if (substr($className, 0, 1) === '$') {
                    $message = 'The typehint seems to be missing for `%s`';
                }
                $phpCsFile->addError(sprintf($message, $className), $classNameIndex, 'ClassNameInvalid');

                continue;
            }
            $classNames[$key] = $useStatement . ($arrayOfObject ? str_repeat('[]', $arrayOfObject) : '');
            $result[$className . ($arrayOfObject ? str_repeat('[]', $arrayOfObject) : '')] = $classNames[$key];
        }

        return $result;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param string $className
     *
     * @return string|null
     */
    protected function findUseStatementForClassName(File $phpCsFile, string $className): ?string
    {
        $useStatements = $this->parseUseStatements($phpCsFile);
        if (!isset($useStatements[$className])) {
            $useStatement = $this->findInSameNameSpace($phpCsFile, $className);
            if ($useStatement) {
                return $useStatement;
            }

            return null;
        }

        return $useStatements[$className];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param string $className
     *
     * @return string|null
     */
    protected function findInSameNameSpace(File $phpCsFile, string $className): ?string
    {
        $currentNameSpace = $this->getNamespace($phpCsFile);
        if (!$currentNameSpace) {
            return null;
        }

        $file = $phpCsFile->getFilename();
        $dir = dirname($file) . DIRECTORY_SEPARATOR;
        if (!file_exists($dir . $className . '.php')) {
            return null;
        }

        return '\\' . $currentNameSpace . '\\' . $className;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getNamespace(File $phpCsFile): string
    {
        $tokens = $phpCsFile->getTokens();

        $namespaceStart = null;
        foreach ($tokens as $id => $token) {
            if ($token['code'] !== T_NAMESPACE) {
                continue;
            }

            $namespaceStart = $id + 1;

            break;
        }
        if (!$namespaceStart) {
            return '';
        }

        $namespaceEnd = $phpCsFile->findNext(
            [
                T_NS_SEPARATOR,
                T_STRING,
                T_WHITESPACE,
            ],
            $namespaceStart,
            null,
            true
        );

        $namespace = trim($phpCsFile->getTokensAsString(($namespaceStart), ($namespaceEnd - $namespaceStart)));

        return $namespace;
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

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string[]
     */
    protected function parseUseStatements(File $phpCsFile): array
    {
        $useStatements = [];
        $tokens = $phpCsFile->getTokens();

        foreach ($tokens as $id => $token) {
            if ($token['type'] !== 'T_USE') {
                continue;
            }

            $endIndex = $phpCsFile->findEndOfStatement($id);
            $useStatement = '';
            for ($i = $id + 2; $i < $endIndex; $i++) {
                $useStatement .= $tokens[$i]['content'];
            }

            $useStatement = trim($useStatement);

            if (strpos($useStatement, ' as ') !== false) {
                [$useStatement, $className] = explode(' as ', $useStatement);
            } else {
                $className = $useStatement;
                if (strpos($useStatement, '\\') !== false) {
                    $lastSeparator = strrpos($useStatement, '\\');
                    $className = substr($useStatement, $lastSeparator + 1);
                }
            }

            $useStatement = '\\' . ltrim($useStatement, '\\');

            $useStatements[$className] = $useStatement;
        }

        return $useStatements;
    }

    /**
     * Parses types respecting | union and () grouping.
     *
     * E.g.: `(string|int)[]|\ArrayObject` is parsed as `(string|int)[]` and `\ArrayObject`.
     *
     * The replace map trick is easier than a regex when keeping the () grouping per type.
     *
     * @param string $content
     *
     * @return string[]
     */
    protected function parseTypes(string $content): array
    {
        preg_match_all('#\(.+\)#', $content, $matches);
        if (!$matches[0]) {
            return explode('|', $content);
        }
        $unionTypes = $matches[0];
        $map = [];
        foreach ($unionTypes as $i => $unionType) {
            $content = str_replace($unionType, '{{t' . $i . '}}', $content);
            $map['{{t' . $i . '}}'] = $unionType;
        }

        $types = explode('|', $content);
        foreach ($types as $k => $type) {
            $types[$k] = str_replace(array_keys($map), array_values($map), $type);
        }

        return $types;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $classNameIndex
     * @param string $className
     * @param string[] $subClassNames
     *
     * @return string
     */
    protected function generateClassNameMapForUnionType(
        File $phpCsFile,
        int $classNameIndex,
        string $className,
        array $subClassNames
    ): string {
        foreach ($subClassNames as $i => $subClassName) {
            if (strpos($subClassName, '\\') !== false) {
                continue;
            }

            if (in_array($subClassName, static::$whitelistedTypes, true)) {
                continue;
            }
            $useStatement = $this->findUseStatementForClassName($phpCsFile, $subClassName);
            if (!$useStatement) {
                $message = 'Invalid typehint `%s`';
                if (substr($subClassName, 0, 1) === '$') {
                    $message = 'The typehint seems to be missing for `%s`';
                }
                $phpCsFile->addError(sprintf($message, $subClassName), $classNameIndex, 'ClassNameInvalidUnion');

                continue;
            }
            $subClassNames[$i] = $useStatement;
        }

        return implode('|', $subClassNames);
    }
}
