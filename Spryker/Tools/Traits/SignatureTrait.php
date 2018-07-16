<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Tools\Traits;

use PHP_CodeSniffer\Files\File;

/**
 * Method signature functionality.
 */
trait SignatureTrait
{
    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPtr
     *
     * @return array
     */
    protected function getMethodSignature(File $phpCsFile, int $stackPtr): array
    {
        $parameters = $phpCsFile->getMethodParameters($stackPtr);
        $tokens = $phpCsFile->getTokens();

        $arguments = [];
        foreach ($parameters as $parameter) {
            $defaultIndex = $default = null;

            $possibleEqualIndex = $phpCsFile->findNext([T_EQUAL], $parameter['token'] + 1, $parameter['token'] + 3);
            if ($possibleEqualIndex) {
                $whitelist = [T_CONSTANT_ENCAPSED_STRING, T_TRUE, T_FALSE, T_NULL, T_OPEN_SHORT_ARRAY, T_LNUMBER, T_DNUMBER];
                $possibleDefaultValue = $phpCsFile->findNext($whitelist, $possibleEqualIndex + 1, $possibleEqualIndex + 3);
                if ($possibleDefaultValue) {
                    $defaultIndex = $possibleDefaultValue;
                    $default = null;
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
                    }
                }
            }

            $typehint = $parameter['type_hint'];
            if (substr($typehint, 0, 1) === '?') {
                $typehint = substr($typehint, 1);
            }

            $arguments[] = [
                'variableIndex' => $parameter['token'],
                'variable' => $parameter['name'],
                'typehint' => $typehint,
                'typehintFull' => $parameter['type_hint'],
                'nullable' => $parameter['nullable_type'],
                'defaultIndex' => $defaultIndex,
                'default' => $default,
            ];
        }

        return $arguments;
    }
}
