<?php

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer_File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Tools\Traits\CommentingTrait;

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

    /**
     * @inheritDoc
     */
    public function register()
    {
        return [
            T_FUNCTION,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(PHP_CodeSniffer_File $phpCsFile, $stackPointer)
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
            if (!in_array($tokens[$i]['content'], ['@param'])) {
                continue;
            }

            if (empty($methodSignature[$paramCount])) {
                continue;
            }
            $methodSignatureValue = $methodSignature[$paramCount];
            $paramCount++;

            $classNameIndex = $i + 2;

            if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
                $phpCsFile->addError('Missing type in param doc block', $i);
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

            if ($methodSignatureValue['typehintIndex']) {
                $typeIndex = $methodSignatureValue['typehintIndex'];
                $type = $tokens[$typeIndex]['content'];
                if (!in_array($type, $pieces) && ($type !== 'array' || !$this->containsTypeArray($pieces))) {
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

                if (!in_array($type, $pieces) && ($type !== 'array' || !$this->containsTypeArray($pieces))) {
                    $pieces[] = $type;
                    $error = 'Possible doc block error: `' . $content . '` seems to be missing type `' . $type . '`.';
                    $fix = $phpCsFile->addFixableError($error, $classNameIndex, 'Default');
                    if ($fix) {
                        $content = implode('|', $pieces);
                        $phpCsFile->fixer->replaceToken($classNameIndex, $content . $appendix);
                    }
                }
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     * @param int $stackPtr
     *
     * @return array
     */
    protected function getMethodSignature(PHP_CodeSniffer_File $phpCsFile, $stackPtr)
    {
        $tokens = $phpCsFile->getTokens();

        $startIndex = $phpCsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr + 1);
        $endIndex = $tokens[$startIndex]['parenthesis_closer'];

        $arguments = [];
        $i = $startIndex;
        while ($nextVariableIndex = $phpCsFile->findNext(T_VARIABLE, $i + 1, $endIndex)) {
            $typehintIndex = $defaultIndex = $default = null;
            $possibleTypeHint = $phpCsFile->findPrevious([T_ARRAY_HINT, T_CALLABLE], $nextVariableIndex - 1, $nextVariableIndex - 3);
            if ($possibleTypeHint) {
                $typehintIndex = $possibleTypeHint;
            }

            $possibleEqualIndex = $phpCsFile->findNext([T_EQUAL], $nextVariableIndex + 1, $nextVariableIndex + 3);
            if ($possibleEqualIndex) {
                $whitelist = [T_CONSTANT_ENCAPSED_STRING, T_TRUE, T_FALSE, T_NULL, T_OPEN_SHORT_ARRAY, T_LNUMBER, T_DNUMBER];
                $possibleDefaultValue = $phpCsFile->findNext($whitelist, $possibleEqualIndex + 1, $possibleEqualIndex + 3);
                if ($possibleDefaultValue) {
                    $defaultIndex = $possibleDefaultValue;
                    //$default = $tokens[$defaultIndex]['content'];
                    if ($tokens[$defaultIndex]['code'] === T_CONSTANT_ENCAPSED_STRING) {
                        $default = 'string';
                    } elseif ($tokens[$defaultIndex]['code'] === T_OPEN_SHORT_ARRAY) {
                        $default = 'array';
                    } elseif ($tokens[$defaultIndex]['code'] === T_FALSE || $tokens[$defaultIndex]['code'] === T_TRUE) {
                        $default = 'bool';
                    } elseif ($tokens[$defaultIndex]['code'] === T_LNUMBER) {
                        $default = 'int';
                    } elseif ($tokens[$defaultIndex]['code'] === T_DNUMBER) {
                        $default = 'float';
                    } elseif ($tokens[$defaultIndex]['code'] === T_NULL) {
                        $default = 'null';
                    } else {
                        //die('Invalid default type: ' . $default);
                    }
                }
            }

            $arguments[] = [
                'variable' => $nextVariableIndex,
                'typehintIndex' => $typehintIndex,
                'defaultIndex' => $defaultIndex,
                'default' => $default,
            ];

            $i = $nextVariableIndex;
        }

        return $arguments;
    }

}
