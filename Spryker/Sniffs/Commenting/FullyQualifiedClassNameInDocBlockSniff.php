<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode;
use Spryker\Traits\CommentingTrait;

/**
 * All doc blocks must use FQCN for class names. Extends the dependency
 * `SlevomatCodingStandard.Namespaces.FullyQualifiedClassNameInAnnotation` with
 * a few more edge cases to fix up.
 */
class FullyQualifiedClassNameInDocBlockSniff implements Sniff
{
    use CommentingTrait;

    /**
     * @var array<string>
     */
    public static $whitelistedTypes = [
        'string', 'int', 'integer', 'float', 'bool', 'boolean', 'resource', 'null', 'void', 'callable',
        'array', 'iterable', 'mixed', 'object', 'false', 'true', 'self', 'static', '$this',
    ];

    /**
     * @var array<string>
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
    public function process(File $phpCsFile, $stackPointer): void
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
            if (!$content) {
                continue;
            }

            /** @var \PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode $valueNode */
            $valueNode = static::getValueNode($tokens[$i]['content'], $content);
            if ($valueNode instanceof InvalidTagValueNode || $valueNode instanceof TypelessParamTagValueNode) {
                return;
            }

            $parts = $this->valueNodeParts($valueNode);

            $this->fixClassNames($phpCsFile, $classNameIndex, $parts, $valueNode);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $classNameIndex
     * @param array<string> $classNames
     * @param \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode $valueNode
     *
     * @return void
     */
    protected function fixClassNames(File $phpCsFile, int $classNameIndex, array $classNames, PhpDocTagValueNode $valueNode): void
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
            $newComment = $this->stringifyValueNode($classNames, $valueNode);

            $phpCsFile->fixer->replaceToken($classNameIndex, $newComment);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $classNameIndex
     * @param array<string> $classNames
     *
     * @return array<string>
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
            true,
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
     * @return array<string>
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
}
