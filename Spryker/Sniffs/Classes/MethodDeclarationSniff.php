<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\AbstractScopeSniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * PSR2_Sniffs_Methods_MethodDeclarationSniff.
 *
 * Checks that the method declaration is correct.
 *
 * @author Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version Release: @package_version@
 * @link http://pear.php.net/package/PHP_CodeSniffer
 */
class MethodDeclarationSniff extends AbstractScopeSniff
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct([T_CLASS, T_INTERFACE], [T_FUNCTION]);
    }

    /**
     * @inheritDoc
     */
    protected function processTokenWithinScope(File $phpcsFile, $stackPtr, $currScope)
    {
        $tokens = $phpcsFile->getTokens();

        $methodName = $phpcsFile->getDeclarationName($stackPtr);
        if ($methodName === null) {
            // Ignore closures.
            return;
        }

        $visibility = 0;
        $static = 0;
        $abstract = 0;
        $final = 0;

        $find = Tokens::$methodPrefixes;
        $find[] = T_WHITESPACE;
        $prev = $phpcsFile->findPrevious($find, ($stackPtr - 1), null, true);
        if (!$prev) {
            return;
        }

        $prefix = $stackPtr;
        while (($prefix = $phpcsFile->findPrevious(Tokens::$methodPrefixes, ($prefix - 1), $prev)) !== false) {
            switch ($tokens[$prefix]['code']) {
                case T_STATIC:
                    $static = $prefix;

                    break;
                case T_ABSTRACT:
                    $abstract = $prefix;

                    break;
                case T_FINAL:
                    $final = $prefix;

                    break;
                default:
                    $visibility = $prefix;

                    break;
            }
        }

        $fixes = [];

        if ($visibility !== 0 && $final > $visibility) {
            $error = 'The final declaration must precede the visibility declaration';
            $fix = $phpcsFile->addFixableError($error, $final, 'FinalAfterVisibility');
            if ($fix === true) {
                $fixes[$final] = '';
                $fixes[($final + 1)] = '';
                if (isset($fixes[$visibility]) === true) {
                    $fixes[$visibility] = 'final ' . $fixes[$visibility];
                } else {
                    $fixes[$visibility] = 'final ' . $tokens[$visibility]['content'];
                }
            }
        }

        if ($visibility !== 0 && $abstract > $visibility) {
            $error = 'The abstract declaration must precede the visibility declaration';
            $fix = $phpcsFile->addFixableError($error, $abstract, 'AbstractAfterVisibility');
            if ($fix === true) {
                $fixes[$abstract] = '';
                $fixes[($abstract + 1)] = '';
                if (isset($fixes[$visibility]) === true) {
                    $fixes[$visibility] = 'abstract ' . $fixes[$visibility];
                } else {
                    $fixes[$visibility] = 'abstract ' . $tokens[$visibility]['content'];
                }
            }
        }

        if ($static !== 0 && $static < $visibility) {
            $error = 'The static declaration must come after the visibility declaration';
            $fix = $phpcsFile->addFixableError($error, $static, 'StaticBeforeVisibility');
            if ($fix === true) {
                $fixes[$static] = '';
                $fixes[($static + 1)] = '';
                if (isset($fixes[$visibility]) === true) {
                    $fixes[$visibility] = $fixes[$visibility] . ' static';
                } else {
                    $fixes[$visibility] = $tokens[$visibility]['content'] . ' static';
                }
            }
        }

        if ($visibility && $final) {
            if ($tokens[$visibility]['content'] === 'private') {
                $error = 'Methods marked as final can\'t be private in PHP 8+';
                $phpcsFile->addError($error, $static, 'FinalVisibility');
            }
        }

        // Batch all the fixes together to reduce the possibility of conflicts.
        if ($fixes) {
            $phpcsFile->fixer->beginChangeset();
            foreach ($fixes as $index => $content) {
                $phpcsFile->fixer->replaceToken($index, $content);
            }

            $phpcsFile->fixer->endChangeset();
        }
    }

    /**
     * @inheritDoc
     */
    protected function processTokenOutsideScope(File $phpcsFile, $stackPtr)
    {
        // Nothing to be done here.
    }
}
