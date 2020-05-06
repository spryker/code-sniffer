<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Tools\Traits\CommentingTrait;
use Spryker\Tools\Traits\SignatureTrait;

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
    public function process(File $phpCsFile, $stackPointer)
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

            $appendix = '';
            $spaceIndex = strpos($content, ' ');
            if ($spaceIndex) {
                $appendix = substr($content, $spaceIndex);
                $content = substr($content, 0, $spaceIndex);
            }
            if (empty($content)) {
                continue;
            }

            if (empty($methodSignatureValue['typehint']) && empty($methodSignatureValue['default'])) {
                continue;
            }

            $pieces = explode('|', $content);
            // We skip for mixed
            if (in_array('mixed', $pieces, true)) {
                continue;
            }

            if ($methodSignatureValue['typehint'] && in_array($methodSignatureValue['typehint'], ['array', 'string', 'int', 'bool', 'float', 'self', 'parent'], true)) {
                $type = $methodSignatureValue['typehint'];
                if (!$this->containsType($type, $pieces) && ($type !== 'array' || !$this->containsTypeArray($pieces))) {
                    $pieces[] = $type;
                    $error = 'Possible doc block error: `' . $content . '` seems to be missing type `' . $type . '`.';
                    $fix = $phpCsFile->addFixableError($error, $classNameIndex, 'Typehint');
                    if ($fix) {
                        $content = implode('|', $pieces);
                        $phpCsFile->fixer->replaceToken($classNameIndex, $content . $appendix);
                    }
                }
            }
            if ($methodSignatureValue['default']) {
                $type = $methodSignatureValue['default'];

                if (!in_array($type, $pieces, true) && ($type !== 'array' || !$this->containsTypeArray($pieces))) {
                    $pieces[] = $type;
                    $error = 'Possible doc block error: `' . $content . '` seems to be missing type `' . $type . '`.';
                    $fix = $phpCsFile->addFixableError($error, $classNameIndex, 'Default');
                    if ($fix) {
                        $content = implode('|', $pieces);
                        $phpCsFile->fixer->replaceToken($classNameIndex, $content . $appendix);
                    }
                }
            }

            if ($methodSignatureValue['nullable']) {
                $type = 'null';

                if (!in_array($type, $pieces, true)) {
                    $pieces[] = $type;
                    $error = 'Doc block error: `' . $content . '` seems to be missing type `' . $type . '`.';
                    $fix = $phpCsFile->addFixableError($error, $classNameIndex, 'Default');
                    if ($fix) {
                        $content = implode('|', $pieces);
                        $phpCsFile->fixer->replaceToken($classNameIndex, $content . $appendix);
                    }
                }
            }

            if (!$methodSignatureValue['default'] && !$methodSignatureValue['nullable']) {
                $error = 'Doc block error: `' . $content . '` seems to be having a wrong `null` type hinted, argument is not nullable though.';
                if (in_array('null', $pieces, true)) {
                    $fix = $phpCsFile->addFixableError($error, $classNameIndex, 'WrongNullable');
                    if ($fix) {
                        foreach ($pieces as $k => $v) {
                            if ($v === 'null') {
                                unset($pieces[$k]);
                            }
                        }
                        $content = implode('|', $pieces);
                        $phpCsFile->fixer->replaceToken($classNameIndex, $content . $appendix);
                    }
                }
            }
        }
    }

    /**
     * @param string $type
     * @param string[] $pieces
     *
     * @return bool
     */
    protected function containsType(string $type, array $pieces): bool
    {
        if (in_array($type, $pieces, true)) {
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

        return in_array($longType, $pieces, true);
    }
}
