<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Traits;

use PHP_CodeSniffer\Files\File;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ThrowsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use ReflectionClass;

/**
 * Common functionality around commenting.
 */
trait CommentingTrait
{
    /**
     * @param string $tagName tag name
     * @param string $tagComment tag comment
     *
     * @return \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode
     */
    protected static function getValueNode(string $tagName, string $tagComment): PhpDocTagValueNode
    {
        static $phpDocParser;
        static $phpDocLexer;
        if (!$phpDocParser || !$phpDocLexer) {
            [$phpDocParser, $phpDocLexer] = static::createPhpDocParser();
        }

        return $phpDocParser->parseTagValue(new TokenIterator($phpDocLexer->tokenize($tagComment)), $tagName);
    }

    /**
     * Builds a parser/lexer pair that works with both phpstan/phpdoc-parser v1 and v2.
     *
     * v2 requires a `ParserConfig` as the first constructor argument on the lexer and every
     * parser; v1 has no such argument. The instances are created through reflection so that
     * neither the v1 nor the v2 call shape appears as a static literal that static analysis
     * would reject against whichever single version happens to be installed.
     *
     * @return array{\PHPStan\PhpDocParser\Parser\PhpDocParser, \PHPStan\PhpDocParser\Lexer\Lexer}
     */
    protected static function createPhpDocParser(): array
    {
        $parserConfigClass = 'PHPStan\PhpDocParser\ParserConfig';

        $configArguments = [];
        if (class_exists($parserConfigClass)) {
            $configArguments[] = (new ReflectionClass($parserConfigClass))
                ->newInstanceArgs([['lines' => true, 'indexes' => true]]);
        }

        $constExprParser = (new ReflectionClass(ConstExprParser::class))->newInstanceArgs($configArguments);
        $typeParser = (new ReflectionClass(TypeParser::class))->newInstanceArgs(array_merge($configArguments, [$constExprParser]));
        $phpDocParser = (new ReflectionClass(PhpDocParser::class))->newInstanceArgs(array_merge($configArguments, [$typeParser, $constExprParser]));
        $phpDocLexer = (new ReflectionClass(Lexer::class))->newInstanceArgs($configArguments);

        return [$phpDocParser, $phpDocLexer];
    }

    /**
     * @param \PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode $valueNode
     *
     * @return array<string>
     */
    protected function valueNodeParts(PhpDocTagValueNode $valueNode): array
    {
        if ($valueNode instanceof MethodTagValueNode) {
            $types = [$valueNode->returnType];
        } elseif ($valueNode instanceof GenericTagValueNode) {
            $types = [$valueNode];
        } elseif ($valueNode->type instanceof UnionTypeNode) {
            $types = $valueNode->type->types;
        } else {
            $types = [$valueNode->type];
        }

        $result = [];
        foreach ($types as $type) {
            $result[] = (string)$type;
        }

        return $result;
    }

    /**
     * @param array<string> $parts
     * @param \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode $valueNode
     *
     * @return string
     */
    protected function stringifyValueNode(array $parts, PhpDocTagValueNode $valueNode): string
    {
        if ($valueNode instanceof ParamTagValueNode) {
            return trim(sprintf(
                '%s %s%s %s',
                implode('|', $parts),
                $valueNode->isVariadic ? '...' : '',
                $valueNode->parameterName,
                $valueNode->description,
            ));
        }
        if ($valueNode instanceof ReturnTagValueNode) {
            return trim(sprintf(
                '%s%s',
                implode('|', $parts),
                $valueNode->description,
            ));
        }
        if ($valueNode instanceof MethodTagValueNode) {
            return trim(sprintf(
                '%s %s() %s',
                implode('|', $parts),
                $valueNode->methodName,
                $valueNode->description,
            ));
        }
        if ($valueNode instanceof VarTagValueNode) {
            return trim(sprintf(
                '%s %s%s',
                implode('|', $parts),
                $valueNode->variableName,
                $valueNode->description,
            ));
        }
        if ($valueNode instanceof ThrowsTagValueNode) {
            return trim(sprintf(
                '%s %s',
                implode('|', $parts),
                $valueNode->description,
            ));
        }

        return trim(implode('|', $parts));
    }

    /**
     * Looks for either `@inheritDoc` or `{@inheritDoc}`.
     * Also allows `@inheritdoc` or `{@inheritdoc}` aliases.
     *
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     * @param string $needle
     *
     * @return bool
     */
    protected function hasInheritDoc(File $phpCsFile, $docBlockStartIndex, $docBlockEndIndex, $needle = '@inheritDoc')
    {
        $tokens = $phpCsFile->getTokens();

        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; ++$i) {
            if (empty($tokens[$i]['content'])) {
                continue;
            }
            if (stripos($tokens[$i]['content'], $needle) === false) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Allow \Foo\Bar[] or array<\Foo\Bar> to pass as array.
     *
     * @param array<string> $docBlockTypes
     * @param string $iterableType
     *
     * @return bool
     */
    protected function containsTypeArray(array $docBlockTypes, string $iterableType = 'array'): bool
    {
        foreach ($docBlockTypes as $docBlockType) {
            if (strpos($docBlockType, '[]') !== false || strpos($docBlockType, $iterableType . '<') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks for ...<...>.
     *
     * @param array<string> $docBlockTypes
     *
     * @return bool
     */
    protected function containsIterableSyntax(array $docBlockTypes): bool
    {
        foreach ($docBlockTypes as $docBlockType) {
            if (strpos($docBlockType, '<') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<\PHPStan\PhpDocParser\Ast\Type\TypeNode|string> $typeNodes type nodes
     *
     * @return string
     */
    protected function renderUnionTypes(array $typeNodes): string
    {
        return (string)preg_replace(
            ['/ ([\|&]) /', '/<\(/', '/\)>/', '/\), /', '/, \(/'],
            ['${1}', '<', '>', ', ', ', '],
            implode('|', $typeNodes),
        );
    }
}
