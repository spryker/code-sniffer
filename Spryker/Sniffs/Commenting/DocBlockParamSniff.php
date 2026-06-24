<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Traits\CommentingTrait;
use Spryker\Traits\SignatureTrait;

/**
 * Makes sure doc block `@param` tags are consistent with the method signature.
 *
 * A `@param` tag is OPTIONAL when it would only repeat the native type hint (e.g. `@param string $x`
 * for `string $x`). It may be present (backwards compatibility) or omitted. A `@param` tag is
 * REQUIRED only when the native type cannot express the full type: array/iterable element types and
 * shapes (`@param array<string> $x`) and parameters with no native type hint at all. Any `@param`
 * that is present must reference a real parameter.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockParamSniff extends AbstractSprykerSniff
{
    use CommentingTrait;
    use SignatureTrait;

    /**
     * Native type hints whose `@param` may be omitted carry no element type or shape; everything
     * else (collections and untyped params) still needs a `@param`.
     *
     * @var array<string>
     */
    protected const LOSSY_TYPE_HINTS = ['array', 'iterable'];

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

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        if ($this->hasInheritDoc($phpCsFile, $docBlockStartIndex, $docBlockEndIndex)) {
            return;
        }

        $methodSignature = $this->getMethodSignature($phpCsFile, $stackPointer);
        if (!$methodSignature) {
            $this->assertNoParams($phpCsFile, $docBlockStartIndex, $docBlockEndIndex);

            return;
        }

        $docBlockParams = [];
        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if (!in_array($tokens[$i]['content'], ['@param'], true)) {
                continue;
            }

            $classNameIndex = $i + 2;

            if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
                $phpCsFile->addError('Missing type in param doc block', $i, 'MissingType');

                continue;
            }

            $content = $tokens[$classNameIndex]['content'];

            $appendix = '';
            $spacePos = strpos($content, ' ');
            if ($spacePos) {
                $appendix = substr($content, $spacePos);
                $content = substr($content, 0, $spacePos);
            }

            preg_match('/\$[^\s]+/', $appendix, $matches);
            $variable = $matches ? $matches[0] : '';

            $docBlockParams[] = [
                'index' => $classNameIndex,
                'type' => $content,
                'variable' => $variable,
                'appendix' => $appendix,
            ];
        }

        $signatureByName = [];
        foreach ($methodSignature as $methodParam) {
            $signatureByName[$tokens[$methodParam['variableIndex']]['content']] = $methodParam;
        }

        // Every `@param` that is present must reference a real parameter. A documented subset is
        // allowed: redundant scalar/object params may be omitted, so we no longer require a 1:1 count.
        $documentedNames = [];
        foreach ($docBlockParams as $docBlockParam) {
            $variable = $docBlockParam['variable'];

            // Type field actually holds the variable (missing type) - other sniffers report that.
            if ($variable === '' || strpos($docBlockParam['type'], '$') !== false) {
                continue;
            }

            if (!array_key_exists($variable, $signatureByName)) {
                $error = 'Doc Block param `' . $variable . '` does not match any method parameter and should be removed';
                $phpCsFile->addError($error, $docBlockParam['index'], 'ExtraParam');

                continue;
            }

            $documentedNames[$variable] = true;
        }

        // A `@param` is mandatory only when the native type hint cannot carry the full type:
        // array/iterable (element type and shape) and parameters with no native type hint at all.
        foreach ($signatureByName as $variableName => $methodParam) {
            if (isset($documentedNames[$variableName])) {
                continue;
            }

            $typeHint = $methodParam['typehint'];
            if ($typeHint !== '' && !$this->hasLossyType($typeHint)) {
                continue;
            }

            $error = 'Doc Block `@param` for `' . $variableName . '` is required: native type `'
                . ($typeHint !== '' ? $typeHint : 'none') . '` cannot express the element type or shape';
            $phpCsFile->addError($error, $stackPointer, 'RequiredParamMissing');
        }
    }

    /**
     * A union type expresses the full type only when none of its members is lossy: `array|string`
     * still hides the array element type and shape, so it keeps requiring a `@param`.
     *
     * @param string $typeHint
     *
     * @return bool
     */
    protected function hasLossyType(string $typeHint): bool
    {
        foreach (explode('|', $typeHint) as $member) {
            if (in_array(ltrim($member, '?'), static::LOSSY_TYPE_HINTS, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return void
     */
    protected function assertNoParams(File $phpCsFile, int $docBlockStartIndex, int $docBlockEndIndex): void
    {
        $tokens = $phpCsFile->getTokens();

        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if ($tokens[$i]['content'] !== '@param') {
                continue;
            }

            $phpCsFile->addError('Doc Block param does not match method signature and should be removed', $i, 'ExtraParam');
        }
    }
}
