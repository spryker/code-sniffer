<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use SlevomatCodingStandard\Helpers\Annotation\Annotation;
use SlevomatCodingStandard\Helpers\Annotation\GenericAnnotation;
use SlevomatCodingStandard\Helpers\Annotation\ParameterAnnotation;
use SlevomatCodingStandard\Helpers\Annotation\ReturnAnnotation;
use SlevomatCodingStandard\Helpers\AnnotationHelper;
use SlevomatCodingStandard\Helpers\AnnotationTypeHelper;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use SlevomatCodingStandard\Helpers\NamespaceHelper;
use SlevomatCodingStandard\Helpers\SniffSettingsHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use SlevomatCodingStandard\Helpers\TypeHintHelper;

/**
 * Fixed version of Slevomatic, touching collection objects the right way.
 *
 * @see https://github.com/slevomat/coding-standard/issues/1296
 */
class DisallowArrayTypeHintSyntaxSniff implements Sniff
{
    /**
     * @var string
     */
    public const CODE_DISALLOWED_ARRAY_TYPE_HINT_SYNTAX = 'DisallowedArrayTypeHintSyntax';

    /**
     * @var array<string>
     */
    public $traversableTypeHints = [];

    /**
     * @var array<string, int>|null
     */
    protected $normalizedTraversableTypeHints;

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_DOC_COMMENT_OPEN_TAG,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $docCommentOpenPointer): void
    {
        $annotations = AnnotationHelper::getAnnotations($phpcsFile, $docCommentOpenPointer);

        foreach ($annotations as $annotationByName) {
            foreach ($annotationByName as $annotation) {
                if ($annotation instanceof GenericAnnotation) {
                    continue;
                }

                if ($annotation->isInvalid()) {
                    continue;
                }

                if ($this->isGenericObjectCollection($annotation)) {
                    $this->fixGenericObjectCollection($phpcsFile, $annotation);

                    continue;
                }

                foreach (AnnotationHelper::getAnnotationTypes($annotation) as $annotationType) {
                    $unionTypeNodes = AnnotationTypeHelper::getUnionTypeNodes($annotationType);
                    foreach ($this->getArrayTypeNodes($annotationType) as $arrayTypeNode) {
                        $fix = $phpcsFile->addFixableError(
                            sprintf(
                                'Usage of array type hint syntax in "%s" is disallowed, use generic type hint syntax instead.',
                                AnnotationTypeHelper::export($arrayTypeNode),
                            ),
                            $annotation->getStartPointer(),
                            static::CODE_DISALLOWED_ARRAY_TYPE_HINT_SYNTAX,
                        );

                        if (!$fix) {
                            continue;
                        }

                        $unionTypeNode = $this->findUnionTypeThatContainsArrayType($arrayTypeNode, $unionTypeNodes);

                        if ($unionTypeNode !== null) {
                            $genericIdentifier = $this->findGenericIdentifier($phpcsFile, $unionTypeNode, $annotation);
                            if ($genericIdentifier !== null) {
                                $genericTypeNode = new GenericTypeNode(
                                    new IdentifierTypeNode($genericIdentifier),
                                    [$this->fixArrayNode($arrayTypeNode->type)],
                                );
                                $fixedAnnotationContent = AnnotationHelper::fixAnnotationType(
                                    $phpcsFile,
                                    $annotation,
                                    $unionTypeNode,
                                    $genericTypeNode,
                                );
                            } else {
                                $genericTypeNode = new GenericTypeNode(
                                    new IdentifierTypeNode('array'),
                                    [$this->fixArrayNode($arrayTypeNode->type)],
                                );
                                $fixedAnnotationContent = AnnotationHelper::fixAnnotationType(
                                    $phpcsFile,
                                    $annotation,
                                    $arrayTypeNode,
                                    $genericTypeNode,
                                );
                            }
                        } else {
                            $genericIdentifier = $this->findGenericIdentifier($phpcsFile, $arrayTypeNode, $annotation) ?? 'array';

                            $genericTypeNode = new GenericTypeNode(
                                new IdentifierTypeNode($genericIdentifier),
                                [$this->fixArrayNode($arrayTypeNode->type)],
                            );
                            $fixedAnnotationContent = AnnotationHelper::fixAnnotationType(
                                $phpcsFile,
                                $annotation,
                                $arrayTypeNode,
                                $genericTypeNode,
                            );
                        }

                        $phpcsFile->fixer->beginChangeset();
                        $phpcsFile->fixer->replaceToken($annotation->getStartPointer(), $fixedAnnotationContent);
                        for ($i = $annotation->getStartPointer() + 1; $i <= $annotation->getEndPointer(); $i++) {
                            $phpcsFile->fixer->replaceToken($i, '');
                        }
                        $phpcsFile->fixer->endChangeset();
                    }
                }
            }
        }
    }

    /**
     * @param \PHPStan\PhpDocParser\Ast\Type\TypeNode $typeNode
     *
     * @return array<\PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode>
     */
    public function getArrayTypeNodes(TypeNode $typeNode): array
    {
        $arrayTypeNodes = AnnotationTypeHelper::getArrayTypeNodes($typeNode);

        $arrayTypeNodesToIgnore = [];
        foreach ($arrayTypeNodes as $arrayTypeNode) {
            if (!($arrayTypeNode->type instanceof ArrayTypeNode)) {
                continue;
            }

            $arrayTypeNodesToIgnore[] = $arrayTypeNode->type;
        }

        foreach ($arrayTypeNodes as $no => $arrayTypeNode) {
            if (!in_array($arrayTypeNode, $arrayTypeNodesToIgnore, true)) {
                continue;
            }

            unset($arrayTypeNodes[$no]);
        }

        return $arrayTypeNodes;
    }

    /**
     * @param \PHPStan\PhpDocParser\Ast\Type\TypeNode $node
     *
     * @return \PHPStan\PhpDocParser\Ast\Type\TypeNode
     */
    protected function fixArrayNode(TypeNode $node): TypeNode
    {
        if (!$node instanceof ArrayTypeNode) {
            return $node;
        }

        return new GenericTypeNode(new IdentifierTypeNode('array'), [$this->fixArrayNode($node->type)]);
    }

    /**
     * @param \PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode $arrayTypeNode
     * @param array<\PHPStan\PhpDocParser\Ast\Type\UnionTypeNode> $unionTypeNodes
     *
     * @return \PHPStan\PhpDocParser\Ast\Type\UnionTypeNode|null
     */
    protected function findUnionTypeThatContainsArrayType(ArrayTypeNode $arrayTypeNode, array $unionTypeNodes): ?UnionTypeNode
    {
        foreach ($unionTypeNodes as $unionTypeNode) {
            if (in_array($arrayTypeNode, $unionTypeNode->types, true)) {
                return $unionTypeNode;
            }
        }

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param \PHPStan\PhpDocParser\Ast\Type\TypeNode $typeNode
     * @param \SlevomatCodingStandard\Helpers\Annotation\Annotation $annotation
     *
     * @return string|null
     */
    protected function findGenericIdentifier(File $phpcsFile, TypeNode $typeNode, Annotation $annotation): ?string
    {
        if (!$typeNode instanceof UnionTypeNode) {
            if (!$annotation instanceof ParameterAnnotation && !$annotation instanceof ReturnAnnotation) {
                return null;
            }

            $functionPointer = TokenHelper::findNext($phpcsFile, TokenHelper::$functionTokenCodes, $annotation->getStartPointer() + 1);

            if ($functionPointer === null || $phpcsFile->getTokens()[$functionPointer]['code'] !== T_FUNCTION) {
                return null;
            }

            if ($annotation instanceof ParameterAnnotation) {
                $parameterTypeHints = FunctionHelper::getParametersTypeHints($phpcsFile, $functionPointer);

                return array_key_exists(
                    $annotation->getParameterName(),
                    $parameterTypeHints,
                ) && $parameterTypeHints[$annotation->getParameterName()] !== null
                    ? $parameterTypeHints[$annotation->getParameterName()]->getTypeHint()
                    : null;
            }

            $returnType = FunctionHelper::findReturnTypeHint($phpcsFile, $functionPointer);

            return $returnType !== null ? $returnType->getTypeHint() : null;
        }

        if (count($typeNode->types) !== 2) {
            return null;
        }

        if (
            $typeNode->types[0] instanceof ArrayTypeNode
            && $typeNode->types[1] instanceof IdentifierTypeNode
            && $this->isTraversableType(
                TypeHintHelper::getFullyQualifiedTypeHint($phpcsFile, $annotation->getStartPointer(), $typeNode->types[1]->name),
            )
        ) {
            return $typeNode->types[1]->name;
        }

        if (
            $typeNode->types[1] instanceof ArrayTypeNode
            && $typeNode->types[0] instanceof IdentifierTypeNode
            && $this->isTraversableType(
                TypeHintHelper::getFullyQualifiedTypeHint($phpcsFile, $annotation->getStartPointer(), $typeNode->types[0]->name),
            )
        ) {
            return $typeNode->types[0]->name;
        }

        return null;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    protected function isTraversableType(string $type): bool
    {
        return TypeHintHelper::isSimpleIterableTypeHint($type) || array_key_exists($type, $this->getNormalizedTraversableTypeHints());
    }

    /**
     * @return array<string, int>
     */
    protected function getNormalizedTraversableTypeHints(): array
    {
        if ($this->normalizedTraversableTypeHints === null) {
            $this->normalizedTraversableTypeHints = array_flip(array_map(static function (string $typeHint): string {
                return NamespaceHelper::isFullyQualifiedName($typeHint)
                    ? $typeHint
                    : sprintf('%s%s', NamespaceHelper::NAMESPACE_SEPARATOR, $typeHint);
            }, SniffSettingsHelper::normalizeArray($this->traversableTypeHints)));
        }

        return $this->normalizedTraversableTypeHints;
    }

    /**
     * @param \SlevomatCodingStandard\Helpers\Annotation\Annotation $annotation
     *
     * @return bool
     */
    protected function isGenericObjectCollection(Annotation $annotation): bool
    {
        //@phpstan-ignore-next-line
        foreach (AnnotationHelper::getAnnotationTypes($annotation) as $annotationType) {
            if ($annotationType instanceof UnionTypeNode) {
                if ($this->isUnionTypeGenericObjectCollection($annotationType)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param \PHPStan\PhpDocParser\Ast\Type\UnionTypeNode $unionTypeNode
     *
     * @return bool
     */
    protected function isUnionTypeGenericObjectCollection(UnionTypeNode $unionTypeNode): bool
    {
        return $this->hasGenericObject($unionTypeNode->types)
            && $this->containsArrayTypeNode($unionTypeNode->types);
    }

    /**
     * These generic object collections are not yet understood by IDEs like PHPStorm.
     *
     * @param array<\PHPStan\PhpDocParser\Ast\Type\TypeNode> $types
     *
     * @return bool
     */
    protected function hasGenericObject(array $types): bool
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
     * @param \PHPStan\PhpDocParser\Ast\Type\TypeNode $typeNode
     *
     * @return bool
     */
    protected function isGenericObject(TypeNode $typeNode): bool
    {
        return $typeNode instanceof IdentifierTypeNode && strpos((string)$typeNode, '\\') === 0;
    }

    /**
     * @param array<\PHPStan\PhpDocParser\Ast\Type\TypeNode> $types
     *
     * @return bool
     */
    protected function containsArrayTypeNode(array $types): bool
    {
        foreach ($types as $type) {
            if (!$type instanceof ArrayTypeNode) {
                continue;
            }

            if ($type->type instanceof IdentifierTypeNode || $type->type instanceof ArrayTypeNode) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \PHPStan\PhpDocParser\Ast\Type\TypeNode $typeNode
     *
     * @return bool
     */
    protected function isArrayTypeNode(TypeNode $typeNode): bool
    {
        return $typeNode instanceof ArrayTypeNode &&
            ($typeNode->type instanceof IdentifierTypeNode || $typeNode->type instanceof ArrayTypeNode);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param \SlevomatCodingStandard\Helpers\Annotation\VariableAnnotation|\SlevomatCodingStandard\Helpers\Annotation\ParameterAnnotation|\SlevomatCodingStandard\Helpers\Annotation\ReturnAnnotation|\SlevomatCodingStandard\Helpers\Annotation\ThrowsAnnotation|\SlevomatCodingStandard\Helpers\Annotation\PropertyAnnotation|\SlevomatCodingStandard\Helpers\Annotation\MethodAnnotation|\SlevomatCodingStandard\Helpers\Annotation\TemplateAnnotation|\SlevomatCodingStandard\Helpers\Annotation\ExtendsAnnotation|\SlevomatCodingStandard\Helpers\Annotation\ImplementsAnnotation|\SlevomatCodingStandard\Helpers\Annotation\UseAnnotation|\SlevomatCodingStandard\Helpers\Annotation\MixinAnnotation|\SlevomatCodingStandard\Helpers\Annotation\TypeAliasAnnotation|\SlevomatCodingStandard\Helpers\Annotation\TypeImportAnnotation $annotation
     *
     * @return void
     */
    protected function fixGenericObjectCollection(File $phpcsFile, Annotation $annotation): void
    {
        $typeNode = AnnotationHelper::getAnnotationTypes($annotation)[0];

        $genericType = null;
        $arrayType = null;
        $unionTypes = [];

        if (!$typeNode instanceof UnionTypeNode) {
            return;
        }

        foreach ($typeNode->types as $type) {
            if ($this->isGenericObject($type)) {
                if ($genericType !== null) {
                    return;
                }

                $genericType = (string)$type;

                continue;
            }

            if ($this->isArrayTypeNode($type)) {
                if ($arrayType !== null) {
                    return;
                }
                /** @var \PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode $arrayTypeNode */
                $arrayTypeNode = $type;
                $arrayType = $this->convertTypeToString($arrayTypeNode->type);

                continue;
            }

            $unionTypes[] = (string)$type;
        }

        if (!$genericType || !$arrayType) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            sprintf(
                'Usage of old type hint syntax for generic in `%s` is disallowed, use generic type hint syntax instead.',
                AnnotationTypeHelper::export($typeNode),
            ),
            $annotation->getStartPointer(),
            static::CODE_DISALLOWED_ARRAY_TYPE_HINT_SYNTAX,
        );

        if (!$fix) {
            return;
        }

        $fixedType = sprintf(
            '%s<%s>',
            $genericType,
            $arrayType,
        );

        array_unshift($unionTypes, $fixedType);

        $fixedType = implode('|', $unionTypes);

        $content = $annotation->getContent() ?? '';
        $spacePosition = strpos($content, ' ');
        if ($spacePosition !== false) {
            $fixedType .= substr($content, $spacePosition);
        }

        $nextToken = $phpcsFile->fixer->getTokenContent($annotation->getEndPointer() + 1);

        if ($nextToken === '*/') {
            $fixedType .= ' ';
        }

        $phpcsFile->fixer->replaceToken($annotation->getStartPointer() + 2, $fixedType);
    }

    /**
     * @param \PHPStan\PhpDocParser\Ast\Type\TypeNode $typeNode
     *
     * @return string
     */
    protected function convertTypeToString(TypeNode $typeNode): string
    {
        if ($typeNode instanceof ArrayTypeNode) {
            return sprintf('array<%s>', $this->convertTypeToString($typeNode->type));
        }

        return (string)$typeNode;
    }
}
