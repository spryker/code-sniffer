<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Traits\CommentingTrait;
use Spryker\Traits\SignatureTrait;

/**
 * Makes sure doc block param types allow `|null`, `|array` etc, when those are used
 * as default values in the method signature.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockParamAllowDefaultValueSniff extends AbstractSprykerSniff
{
    use CommentingTrait;
    use SignatureTrait;

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_FUNCTION,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpCsFile, $stackPointer): void
    {
        $tokens = $phpCsFile->getTokens();

        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);

        if (!$docBlockEndIndex) {
            return;
        }

        $methodSignature = $this->getMethodSignature($phpCsFile, $stackPointer);
        if (!$methodSignature) {
            return;
        }

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        $paramCount = 0;
        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if ($tokens[$i]['content'] !== '@param') {
                continue;
            }

            if (empty($methodSignature[$paramCount])) {
                continue;
            }
            $methodSignatureValue = $methodSignature[$paramCount];
            $paramCount++;

            $classNameIndex = $i + 2;

            if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
                $phpCsFile->addError('Missing type in param doc block', $i, 'TypeMissing');

                continue;
            }

            $content = $tokens[$classNameIndex]['content'];
            if (!$content) {
                continue;
            }

            if (empty($methodSignatureValue['typehint']) && empty($methodSignatureValue['default'])) {
                continue;
            }

            /** @var \PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode $valueNode */
            $valueNode = static::getValueNode($tokens[$i]['content'], $content);
            if ($valueNode instanceof InvalidTagValueNode || $valueNode instanceof TypelessParamTagValueNode) {
                return;
            }
            $parts = $this->valueNodeParts($valueNode);

            // We skip for mixed
            if (in_array('mixed', $parts, true)) {
                continue;
            }

            if ($methodSignatureValue['typehint'] && in_array($methodSignatureValue['typehint'], ['array', 'iterable', 'string', 'int', 'bool', 'float', 'self', 'parent'], true)) {
                $type = $methodSignatureValue['typehint'];
                if (
                    !$this->containsType($type, $parts)
                    && !$this->isPrimitiveGenerics($type, $parts)
                    && !$this->isClassString($type, $parts)
                ) {
                    $parts[] = $type;
                    $error = 'Possible doc block error: `' . $content . '` seems to be missing type `' . $type . '`.';
                    $fix = $phpCsFile->addFixableError($error, $classNameIndex, 'Typehint');
                    if ($fix) {
                        $newComment = trim(sprintf(
                            '%s %s%s %s',
                            implode('|', $parts),
                            $valueNode->isVariadic ? '...' : '',
                            $valueNode->parameterName,
                            $valueNode->description,
                        ));
                        $phpCsFile->fixer->replaceToken($classNameIndex, $newComment);
                    }
                }
            }
            if ($methodSignatureValue['default']) {
                $type = $methodSignatureValue['default'];

                if (
                    !in_array($type, $parts, true)
                    && !$this->isPrimitiveGenerics($type, $parts)
                    && !$this->isClassString($type, $parts)
                ) {
                    $parts[] = $type;
                    $error = 'Possible doc block error: `' . $content . '` seems to be missing type `' . $type . '`.';
                    $fix = $phpCsFile->addFixableError($error, $classNameIndex, 'Default');
                    if ($fix) {
                        $newComment = trim(sprintf(
                            '%s %s%s %s',
                            implode('|', $parts),
                            $valueNode->isVariadic ? '...' : '',
                            $valueNode->parameterName,
                            $valueNode->description,
                        ));
                        $phpCsFile->fixer->replaceToken($classNameIndex, $newComment);
                    }
                }
            }

            if ($methodSignatureValue['nullable']) {
                $type = 'null';

                if (!in_array($type, $parts, true)) {
                    $parts[] = $type;
                    $error = 'Doc block error: `' . $content . '` seems to be missing type `' . $type . '`.';
                    $fix = $phpCsFile->addFixableError($error, $classNameIndex, 'Nullable');
                    if ($fix) {
                        $newComment = trim(sprintf(
                            '%s %s%s %s',
                            implode('|', $parts),
                            $valueNode->isVariadic ? '...' : '',
                            $valueNode->parameterName,
                            $valueNode->description,
                        ));
                        $phpCsFile->fixer->replaceToken($classNameIndex, $newComment);
                    }
                }
            }

            if (!$methodSignatureValue['default'] && !$methodSignatureValue['nullable']) {
                if (!in_array('null', $parts, true) || $methodSignatureValue['typehint'] === 'mixed') {
                    continue;
                }

                $error = 'Doc block error: `' . $content . '` seems to be having a wrong `null` type hinted, argument is not nullable though.';
                $fix = $phpCsFile->addFixableError($error, $classNameIndex, 'WrongNullable');
                if ($fix) {
                    foreach ($parts as $k => $v) {
                        if ($v === 'null') {
                            unset($parts[$k]);
                        }
                    }
                    $newComment = trim(sprintf(
                        '%s %s%s %s',
                        implode('|', $parts),
                        $valueNode->isVariadic ? '...' : '',
                        $valueNode->parameterName,
                        $valueNode->description,
                    ));
                    $phpCsFile->fixer->replaceToken($classNameIndex, $newComment);
                }
            }
        }
    }

    /**
     * @param string $type
     * @param array<string> $parts
     *
     * @return bool
     */
    protected function containsType(string $type, array $parts): bool
    {
        if (in_array($type, $parts, true)) {
            return true;
        }
        $longTypes = [
            'int' => 'integer',
            'bool' => 'boolean',
        ];
        if (!isset($longTypes[$type])) {
            return false;
        }

        $longType = $longTypes[$type];

        return in_array($longType, $parts, true);
    }

    /**
     * @param string $type
     * @param array<string> $parts
     *
     * @return bool
     */
    protected function isPrimitiveGenerics(string $type, array $parts): bool
    {
        $iterableTypes = ['array', 'iterable', 'list'];
        if (!in_array($type, $iterableTypes, true)) {
            return false;
        }

        foreach ($iterableTypes as $iterableType) {
            if ($this->containsTypeArray($parts, $iterableType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $type
     * @param array<string> $parts
     *
     * @return bool
     */
    protected function isClassString(string $type, array $parts): bool
    {
        if ($type !== 'string') {
            return false;
        }

        if (in_array('class-string', $parts, true)) {
            return true;
        }

        foreach ($parts as $part) {
            if (strpos($part, 'class-string<') === 0) {
                return true;
            }
        }

        return false;
    }
}
