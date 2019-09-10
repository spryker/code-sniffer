<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * Makes sure there are spaces between the concatenation operator (.) and
 * the strings being concatenated.
 */
class ConcatenationSpacingSniff implements Sniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_STRING_CONCAT];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $prevIndex = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);

        if ($tokens[($stackPtr - 1)]['code'] !== T_WHITESPACE) {
            $message = 'Expected 1 space before ., but 0 found';
            $phpcsFile->addFixableError($message, $stackPtr, 'MissingBefore');
            $this->addSpace($phpcsFile, $stackPtr - 1);
        } else {
            $content = $tokens[$stackPtr - 1]['content'];
            if ($tokens[$prevIndex]['line'] === $tokens[$stackPtr]['line'] && $content !== ' ') {
                $message = 'Expected 1 space before `.`, but %d found';
                $data = [strlen($content)];
                $fix = $phpcsFile->addFixableError($message, $stackPtr, 'TooManyBefore', $data);
                if ($fix) {
                    $phpcsFile->fixer->replaceToken($stackPtr - 1, ' ');
                }
            }
        }

        $nextIndex = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);

        if ($tokens[($stackPtr + 1)]['code'] !== T_WHITESPACE) {
            $message = 'Expected 1 space after ., but 0 found';
            $phpcsFile->addFixableError($message, $stackPtr, 'MissingAfter');
            $this->addSpace($phpcsFile, $stackPtr);
        } else {
            $content = $tokens[($stackPtr + 1)]['content'];
            if ($tokens[$nextIndex]['line'] === $tokens[$stackPtr]['line'] && $content !== ' ') {
                $message = 'Expected 1 space after `.`, but %d found';
                $data = [strlen($content)];
                $fix = $phpcsFile->addFixableError($message, $stackPtr, 'TooManyAfter', $data);
                if ($fix) {
                    $phpcsFile->fixer->replaceToken($stackPtr + 1, ' ');
                }
            }
        }
    }

    /**
     * Adds a single space on the right sight.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $index
     *
     * @return void
     */
    protected function addSpace(File $phpcsFile, int $index): void
    {
        if ($phpcsFile->fixer->enabled !== true) {
            return;
        }
        $phpcsFile->fixer->addContent($index, ' ');
    }
}
