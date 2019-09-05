<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Squiz_Sniffs_Classes_ClassFileNameSniff.
 *
 * Tests that the file name and the name of the class contained within the file
 * match.
 *
 * @category PHP
 * @package PHP_CodeSniffer
 * @author Greg Sherwood <gsherwood@squiz.net>
 * @author Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version Release: @package_version@
 * @link http://pear.php.net/package/PHP_CodeSniffer
 */
class ClassFileNameSniff extends AbstractSprykerSniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_CLASS,
            T_INTERFACE,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $fullPath = basename($phpcsFile->getFilename());
        $fileName = substr($fullPath, 0, strrpos($fullPath, '.'));
        if ($fileName === '') {
            // No filename probably means STDIN, so we can't do this check.
            return;
        }

        $tokens = $phpcsFile->getTokens();

        $previous = $phpcsFile->findPrevious([T_CLASS, T_INTERFACE], $stackPtr - 1);
        if ($previous) {
            // Probably more than a single declaration per file, we only check first one then.
            return;
        }
        $declaredNameIndex = $phpcsFile->findNext(T_STRING, $stackPtr);
        if ($tokens[$declaredNameIndex]['content'] === $fileName) {
            return;
        }
        $error = '%s name "%s" doesn\'t match filename, expected "%s"';
        $data = [
            ucfirst($tokens[$stackPtr]['content']),
            $tokens[$declaredNameIndex]['content'],
            $fileName,
        ];
        $phpcsFile->addError($error, $stackPtr, 'NoMatch', $data);
    }
}
