<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Traits;

use PHP_CodeSniffer\Files\File;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;

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
        if (!$phpDocParser) {
            $constExprParser = new ConstExprParser();
            $phpDocParser = new PhpDocParser(new TypeParser($constExprParser), $constExprParser);
        }

        static $phpDocLexer;
        if (!$phpDocLexer) {
            $phpDocLexer = new Lexer();
        }

        return $phpDocParser->parseTagValue(new TokenIterator($phpDocLexer->tokenize($tagComment)), $tagName);
    }

    /**
     * @param \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode $valueNode
     *
     * @return array<string>
     */
    protected static function valueNodeParts(PhpDocTagValueNode $valueNode): array
    {
        /** @var \PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode $valueNode */
        if ($valueNode->type instanceof UnionTypeNode) {
            $types = $valueNode->type->types;
        } else {
            $types = [$valueNode->type];
        }

        foreach ($types as $key => $type) {
            $types[$key] = (string)$type;
        }

        return $types;
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
            $content = $tokens[$i]['content'];
            $pos = stripos($content, $needle);
            if ($pos === false) {
                continue;
            }

            if ($pos && strpos($needle, '@') === 0 && substr($content, $pos - 1, $pos) === '{') {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Allow \Foo\Bar[] or array<\Foo\Bar> to pass as array.
     *
     * @param array<string> $docBlockTypes
     *
     * @return bool
     */
    protected function containsTypeArray(array $docBlockTypes): bool
    {
        foreach ($docBlockTypes as $docBlockType) {
            if (strpos($docBlockType, '[]') !== false || strpos($docBlockType, 'array<') === 0) {
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
}
