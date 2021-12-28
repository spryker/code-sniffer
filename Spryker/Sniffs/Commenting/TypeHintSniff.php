<?php

/**
 * MIT License - modified by Spryker Systems GmbH
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link https://github.com/cakephp/cakephp-codesniffer
 * @since CakePHP CodeSniffer 5.0.0
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Traits\CommentingTrait;

/**
 * Verifies order of types in type hints. Also removes duplicates.
 * Fixes invalid/problematic generic declarations back to legacy ones.
 */
class TypeHintSniff extends AbstractSprykerSniff
{
    use CommentingTrait;

    /**
     * Use this to keep legacy collection objects as `\FQCN|type[]` instead of
     * `\FQCN<type>` for all non-trivial object types. This helps IDEs to understand this,
     * as long as they do not yet understand new generics here.
     *
     * @var bool
     */
    public $legacyCollectionObjects = true;

    /**
     * @var array<string>
     */
    protected static $typeHintTags = [
        '@var',
        '@psalm-var',
        '@phpstan-var',
        '@param',
        '@psalm-param',
        '@phpstan-param',
        '@return',
        '@psalm-return',
        '@phpstan-return',
    ];

    /**
     * Highest/First element will be first in list of param or return tag.
     *
     * @var array<string>
     */
    protected static $sortMap = [
        '\\Closure',
        '\\Traversable',
        '\\ArrayAccess',
        '\\ArrayObject',
        '\\Stringable',
        '\\Generator',
        'mixed',
        'callable',
        'resource',
        'object',
        'iterable',
        'list',
        'array',
        'callable-string',
        'class-string',
        'interface-string',
        'scalar',
        'string',
        'float',
        'int',
        'bool',
        'true',
        'false',
        'null',
        'void',
    ];

    /**
     * The following classes are supported for object generics by IDEs like PHPStorm already.
     * E.g. `\ArrayObject<type>` instead of legacy syntax `\ArrayObject|type[]`.
     *
     * @var array<string>
     */
    protected static $genericCollectionClasses = [
        '\\Traversable',
        '\\ArrayAccess',
        '\\ArrayObject',
        '\\Generator',
        '\\Iterator',
    ];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_DOC_COMMENT_OPEN_TAG];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if (!isset($tokens[$stackPtr]['comment_closer'])) {
            return;
        }

        foreach ($tokens[$stackPtr]['comment_tags'] as $tag) {
            if (
                $tokens[$tag + 2]['code'] !== T_DOC_COMMENT_STRING ||
                !in_array($tokens[$tag]['content'], static::$typeHintTags, true)
            ) {
                continue;
            }

            $tagComment = $phpcsFile->fixer->getTokenContent($tag + 2);
            $valueNode = static::getValueNode($tokens[$tag]['content'], $tagComment);
            if ($valueNode instanceof InvalidTagValueNode) {
                continue;
            }

            /** @phpstan-var \PHPStan\PhpDocParser\Ast\Type\GenericTypeNode|\PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode $valueNode */
            if ($valueNode->type instanceof UnionTypeNode) {
                $types = $valueNode->type->types;
            } elseif ($valueNode->type instanceof ArrayTypeNode) {
                $types = [$valueNode->type];
            } elseif ($valueNode->type instanceof GenericTypeNode) {
                $types = [$valueNode->type];
            } else {
                continue;
            }

            $originalTypeHint = $this->renderUnionTypes($types);
            $sortedTypeHint = $this->getSortedTypeHint($types, $tokens[$tag]['content']);
            if ($sortedTypeHint === $originalTypeHint) {
                continue;
            }

            $fix = $phpcsFile->addFixableError(
                '%s type hint is not formatted properly, expected "%s"',
                $tag,
                'IncorrectFormat',
                [$tokens[$tag]['content'], $sortedTypeHint],
            );
            if (!$fix) {
                continue;
            }

            $newComment = $tagComment;
            if ($valueNode instanceof VarTagValueNode) {
                $newComment = trim(sprintf(
                    '%s %s %s',
                    $sortedTypeHint,
                    $valueNode->variableName,
                    $valueNode->description,
                ));
                if ($tagComment[-1] === ' ') {
                    // tags above variables in code have a trailing space
                    $newComment .= ' ';
                }
            } elseif ($valueNode instanceof ParamTagValueNode) {
                $newComment = trim(sprintf(
                    '%s %s%s %s',
                    $sortedTypeHint,
                    $valueNode->isVariadic ? '...' : '',
                    $valueNode->parameterName,
                    $valueNode->description,
                ));
            } elseif ($valueNode instanceof ReturnTagValueNode) {
                $newComment = trim(sprintf(
                    '%s %s',
                    $sortedTypeHint,
                    $valueNode->description,
                ));
            }

            $phpcsFile->fixer->beginChangeset();
            $phpcsFile->fixer->replaceToken($tag + 2, $newComment);
            $phpcsFile->fixer->endChangeset();
        }

        foreach ($tokens[$stackPtr]['comment_tags'] as $key => $tag) {
            if (
                $tokens[$tag + 2]['code'] !== T_DOC_COMMENT_STRING ||
                !in_array($tokens[$tag]['content'], static::$typeHintTags, true)
            ) {
                continue;
            }

            $tagComment = $phpcsFile->fixer->getTokenContent($tag + 2);

            if ($this->isStanTag($tokens[$tag]['content']) && $this->isDuplicate($phpcsFile, $tokens[$tag]['content'], $tagComment, $tokens[$stackPtr]['comment_tags'])) {
                $fix = $phpcsFile->addFixableError('Stan annotation is superfluous and can be removed', $tag, 'Superfluous');
                if ($fix) {
                    $phpcsFile->fixer->beginChangeset();
                    if (isset($tokens[$stackPtr]['comment_tags'][$key + 1])) {
                        for ($i = $tag; $i < $tokens[$stackPtr]['comment_tags'][$key + 1]; $i++) {
                            $phpcsFile->fixer->replaceToken($i, '');
                        }
                    } else {
                        $prevContentIndex = $phpcsFile->findPrevious([T_WHITESPACE, T_DOC_COMMENT_WHITESPACE], $tokens[$stackPtr]['comment_closer'] - 1, null, true);
                        $firstLineIndex = $this->getFirstTokenOfLine($tokens, $tag);
                        for ($i = $firstLineIndex - 1; $i <= $prevContentIndex; $i++) {
                            $phpcsFile->fixer->replaceToken($i, '');
                        }
                    }
                    $phpcsFile->fixer->endChangeset();
                }
            } elseif ($this->isStanTag($tokens[$tag]['content'])) {
                $merchableIndex = $this->findMerchableTag($phpcsFile, $tokens[$tag]['content'], $tagComment, $tokens[$stackPtr]['comment_tags']);
                if ($merchableIndex) {
                    $content = $tokens[$merchableIndex]['content'];
                    $fix = $phpcsFile->addFixableError('Stan annotation can be merged with `' . $content . '`', $tag, 'Mergable');
                    if ($fix) {
                        $phpcsFile->fixer->beginChangeset();

                        // Replace
                        $phpcsFile->fixer->replaceToken($merchableIndex + 2, $tokens[$tag + 2]['content']);

                        // Remove
                        if (isset($tokens[$stackPtr]['comment_tags'][$key + 1])) {
                            for ($i = $tag; $i < $tokens[$stackPtr]['comment_tags'][$key + 1]; $i++) {
                                $phpcsFile->fixer->replaceToken($i, '');
                            }
                        } else {
                            $prevContentIndex = $phpcsFile->findPrevious([T_WHITESPACE, T_DOC_COMMENT_WHITESPACE], $tokens[$stackPtr]['comment_closer'] - 1, null, true);
                            $firstLineIndex = $this->getFirstTokenOfLine($tokens, $tag);
                            for ($i = $firstLineIndex - 1; $i <= $prevContentIndex; $i++) {
                                $phpcsFile->fixer->replaceToken($i, '');
                            }
                        }

                        $phpcsFile->fixer->endChangeset();
                    }
                }
            }
        }
    }

    /**
     * @param array<\PHPStan\PhpDocParser\Ast\Type\TypeNode> $types node types
     * @param string $tag
     *
     * @return string
     */
    protected function getSortedTypeHint(array $types, string $tag): string
    {
        $sortable = array_fill_keys(static::$sortMap, []);
        $unsortable = [];
        foreach ($types as $type) {
            $sortName = null;
            if ($type instanceof IdentifierTypeNode) {
                $sortName = $type->name;
            } elseif ($type instanceof NullableTypeNode) {
                if ($type->type instanceof IdentifierTypeNode) {
                    $sortName = $type->type->name;
                }
            } elseif ($type instanceof ArrayTypeNode) {
                if ($type->type instanceof IdentifierTypeNode) {
                    /** @var \PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode $identifierType */
                    $identifierType = $type->type;
                    $sortName = $identifierType->name;
                } else {
                    $sortName = 'array';
                }
            } elseif ($type instanceof ArrayShapeNode) {
                $sortName = 'array';
            } elseif ($type instanceof GenericTypeNode) {
                if (
                    $this->legacyCollectionObjects
                    && !$this->isStanTag($tag)
                    && $this->isObjectCollection($types)
                    && !$this->isGenericObjectCollection($types)
                    && count($type->genericTypes) === 1
                    && in_array($type->type->name, ['array', 'iterable'], true)
                ) {
                    /** @var \PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode $identifierType */
                    $identifierType = $type->genericTypes[0];
                    $type = new ArrayTypeNode(new IdentifierTypeNode($identifierType->name));
                    $sortName = 'array';
                } elseif (
                    !$this->isStanTag($tag)
                    && substr($type->type->name, 0, 1) === '\\'
                    && !in_array($type->type->name, static::$genericCollectionClasses, true)
                    && count($type->genericTypes) === 1 && $type->genericTypes[0] instanceof IdentifierTypeNode
                ) {
                    /** @var \PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode $identifierType */
                    $identifierType = $type->genericTypes[0];
                    $type = (string)new UnionTypeNode([
                        new IdentifierTypeNode($type->type->name),
                        new ArrayTypeNode(new IdentifierTypeNode($identifierType->name)),
                    ]);
                    $type = substr($type, 1, -1);
                    $sortName = 'array';
                } elseif (in_array($type->type->name, static::$sortMap)) {
                    $sortName = $type->type->name;
                } else {
                    $sortName = 'array';
                }
            }

            if (!$sortName) {
                $unsortable[] = $type;

                continue;
            }

            if (in_array($sortName, static::$sortMap, true)) {
                if ($type instanceof ArrayTypeNode) {
                    array_unshift($sortable[$sortName], $type);
                } else {
                    $sortable[$sortName][] = $type;
                }
            } else {
                $unsortable[] = $type;
            }
        }

        $sorted = [];
        array_walk($sortable, function ($types) use (&$sorted): void {
            $sorted = array_merge($sorted, $types);
        });

        $types = array_merge($unsortable, $sorted);
        $types = $this->makeUnique($types);

        return $this->renderUnionTypes($types);
    }

    /**
     * @param array<\PHPStan\PhpDocParser\Ast\Type\TypeNode|string> $types
     *
     * @return array<\PHPStan\PhpDocParser\Ast\Type\TypeNode|string>
     */
    protected function makeUnique(array $types): array
    {
        $typesAsString = [];

        foreach ($types as $key => $type) {
            $type = (string)$type;
            if (in_array($type, $typesAsString, true)) {
                unset($types[$key]);

                continue;
            }
            $typesAsString[] = $type;
        }

        return $types;
    }

    /**
     * Checks if it is an object collection of any type (\FQCN<type>).
     *
     * @param array<\PHPStan\PhpDocParser\Ast\Type\TypeNode> $types
     *
     * @return bool
     */
    protected function isObjectCollection(array $types): bool
    {
        foreach ($types as $type) {
            if (!$type instanceof IdentifierTypeNode) {
                continue;
            }

            if (strpos((string)$type, '\\') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * These simple generic object collections are already understood by IDEs like PHPStorm.
     *
     * @param array<\PHPStan\PhpDocParser\Ast\Type\TypeNode> $types
     *
     * @return bool
     */
    protected function isGenericObjectCollection(array $types): bool
    {
        foreach ($types as $type) {
            if (!$type instanceof IdentifierTypeNode) {
                continue;
            }

            $className = (string)$type;
            if (strpos((string)$type, '\\') === 0 && in_array($className, static::$genericCollectionClasses, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * We do not want to touch stan tags, as they are usually more accurate than normal tags.
     * Normal tags often need legacy syntax for IDEs to understand them.
     *
     * @param string $tag
     *
     * @return bool
     */
    protected function isStanTag(string $tag): bool
    {
        return strpos($tag, '@phpstan-') === 0 || strpos($tag, '@psalm-') === 0;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param string $tag
     * @param string|null $content
     * @param array<int> $commentTags
     *
     * @return bool
     */
    protected function isDuplicate(File $phpcsFile, string $tag, ?string $content, array $commentTags): bool
    {
        if (!$content) {
            return false;
        }

        $matchingTag = str_replace(['phpstan-', 'psalm-'], '', $tag);

        $tokens = $phpcsFile->getTokens();
        foreach ($commentTags as $commentTag) {
            if ($tokens[$commentTag]['content'] !== $matchingTag) {
                continue;
            }

            $tagComment = $phpcsFile->fixer->getTokenContent($commentTag + 2);

            if ($tagComment === $content) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param string $tag
     * @param string|null $content
     * @param array<int> $commentTags
     *
     * @return int|null
     */
    protected function findMerchableTag(File $phpcsFile, string $tag, ?string $content, array $commentTags): ?int
    {
        if (!$content) {
            return null;
        }

        $types = static::$genericCollectionClasses;
        $types[] = 'array';
        $types[] = 'iterable';
        $callback = function (string &$value): void {
            $value = str_replace('\\', '\\\\', $value);
        };
        array_walk($types, $callback);

        preg_match('/^(' . implode('|', $types) . ')<.+>/', $content, $matches);
        if (!$matches) {
            return null;
        }

        $matchingTag = str_replace(['phpstan-', 'psalm-'], '', $tag);

        $tokens = $phpcsFile->getTokens();
        foreach ($commentTags as $commentTag) {
            if ($tokens[$commentTag]['content'] !== $matchingTag) {
                continue;
            }

            $tagComment = $phpcsFile->fixer->getTokenContent($commentTag + 2);

            preg_match('/^(' . implode('|', $types) . ')( .+)?$/', $tagComment, $matches);

            if ($matches) {
                return $commentTag;
            }
        }

        return null;
    }
}
