<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Traits;

use PHP_CodeSniffer\Files\File;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use SlevomatCodingStandard\Helpers\Annotation;
use SlevomatCodingStandard\Helpers\AnnotationHelper;
use SlevomatCodingStandard\Helpers\DocCommentHelper;
use SlevomatCodingStandard\Helpers\FunctionHelper;

/**
 * Slevomat resolves `X|mixed` to plain `mixed`, so an annotation that unions `mixed` with a
 * generic, array shape or `T[]` compares equal to a native `mixed` hint and is reported — and
 * auto-fixed away — as useless. That deletes the only place the element type is written down.
 * Members carrying such an annotation are skipped entirely.
 */
trait MixedUnionAnnotationTrait
{
    protected function hasDetailedMixedUnionParameterAnnotation(File $phpCsFile, int $functionPointer): bool
    {
        foreach (FunctionHelper::getValidParametersAnnotations($phpCsFile, $functionPointer) as $annotation) {
            if ($this->isDetailedMixedUnionAnnotation($annotation)) {
                return true;
            }
        }

        return false;
    }

    protected function hasDetailedMixedUnionReturnAnnotation(File $phpCsFile, int $functionPointer): bool
    {
        $annotation = FunctionHelper::findReturnAnnotation($phpCsFile, $functionPointer);

        return $annotation !== null && $this->isDetailedMixedUnionAnnotation($annotation);
    }

    protected function hasDetailedMixedUnionVarAnnotation(File $phpCsFile, int $pointer): bool
    {
        $docCommentOpenPointer = DocCommentHelper::findDocCommentOpenPointer($phpCsFile, $pointer);
        if ($docCommentOpenPointer === null) {
            return false;
        }

        foreach (AnnotationHelper::getAnnotations($phpCsFile, $docCommentOpenPointer, '@var') as $annotation) {
            if ($this->isDetailedMixedUnionAnnotation($annotation)) {
                return true;
            }
        }

        return false;
    }

    protected function isDetailedMixedUnionAnnotation(Annotation $annotation): bool
    {
        if ($annotation->isInvalid()) {
            return false;
        }

        $value = $annotation->getValue();
        if (
            !$value instanceof ParamTagValueNode
            && !$value instanceof ReturnTagValueNode
            && !$value instanceof VarTagValueNode
        ) {
            return false;
        }

        return $this->isDetailedMixedUnion($value->type);
    }

    protected function isDetailedMixedUnion(TypeNode $typeNode): bool
    {
        if (!$typeNode instanceof UnionTypeNode) {
            return false;
        }

        $hasMixed = false;
        $hasTypeDetail = false;

        foreach ($typeNode->types as $innerTypeNode) {
            if ($innerTypeNode instanceof IdentifierTypeNode && strtolower($innerTypeNode->name) === 'mixed') {
                $hasMixed = true;

                continue;
            }

            if ($this->carriesTypeDetail($innerTypeNode)) {
                $hasTypeDetail = true;
            }
        }

        return $hasMixed && $hasTypeDetail;
    }

    protected function carriesTypeDetail(TypeNode $typeNode): bool
    {
        return $typeNode instanceof GenericTypeNode
            || $typeNode instanceof ArrayShapeNode
            || $typeNode instanceof ArrayTypeNode;
    }
}
