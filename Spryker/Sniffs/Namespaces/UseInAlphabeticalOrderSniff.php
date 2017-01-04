<?php
/**
 * PHP Version 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         CakePHP CodeSniffer 0.1.10
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Spryker\Sniffs\Namespaces;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Sniff;
use PHP_CodeSniffer_Tokens;

/**
 * Ensures all use statements are in alphabetical order.
 *
 * @author Mark Scherer
 * @license MIT
 */
class UseInAlphabeticalOrderSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * Processed files
     *
     * @var array
     */
    protected $_processed = [];

    /**
     * The list of use statements, their content and scope.
     *
     * @var array
     */
    protected $_uses = [];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [T_USE];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        if (isset($this->_processed[$phpcsFile->getFilename()])) {
            return;
        }

        $this->_uses = [];
        $next = $stackPtr;

        while ($next !== false) {
            $this->_checkUseToken($phpcsFile, $next);
            $next = $phpcsFile->findNext(T_USE, $next + 1);
        }

        // Prevent multiple uses in the same file from entering
        $this->_processed[$phpcsFile->getFilename()] = true;

        foreach ($this->_uses as $scope => $used) {
            $defined = $sorted = array_keys($used);

            natcasesort($sorted);
            $sorted = array_values($sorted);
            if ($sorted === $defined) {
                continue;
            }

            $wrongName = null;
            foreach ($defined as $i => $name) {
                if ($name !== $sorted[$i]) {
                    $wrongName = $name;
                    break;
                }
            }

            $error = 'Use classes must be in alphabetical order.';
            $fix = $phpcsFile->addFixableError($error, $used[$wrongName], 'Order', []);
            if ($fix) {
                $map = [];
                foreach ($sorted as $name) {
                    $tokenIndex = array_shift($used);
                    $tokenIndex = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, $tokenIndex + 1, null, true);
                    $map[$tokenIndex] = $name;
                }

                $phpcsFile->fixer->beginChangeset();

                foreach ($map as $index => $name) {
                    $phpcsFile->fixer->replaceToken($index, $name);
                    $endIndex = $phpcsFile->findNext([T_SEMICOLON, T_OPEN_CURLY_BRACKET], $index + 1);
                    for ($i = $index + 1; $i < $endIndex; $i++) {
                        $phpcsFile->fixer->replaceToken($i, '');
                    }
                }

                $phpcsFile->fixer->endChangeset();
            }
        }
    }

    /**
     * Check all the use tokens in a file.
     *
     * @param \PHP_CodeSniffer_File $phpcsFile The file to check.
     * @param int $stackPtr The index of the first use token.
     *
     * @return void
     */
    protected function _checkUseToken(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        // If the use token is for a closure we want to ignore it.
        $isClosure = $this->_isClosure($phpcsFile, $stackPtr);
        if ($isClosure) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        $content = '';
        $startIndex = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, $stackPtr + 1, null, true);
        $endIndex = $phpcsFile->findNext([T_SEMICOLON, T_OPEN_CURLY_BRACKET], $startIndex + 1);

        for ($i = $startIndex; $i < $endIndex; $i++) {
            $content .= $tokens[$i]['content'];
        }

        // Check for class scoping on use. Traits should be
        // ordered independently.
        $scope = 0;
        if (!empty($tokens[$i]['conditions'])) {
            $scope = key($tokens[$i]['conditions']);
        }
        $this->_uses[$scope][$content] = $stackPtr;
    }

    /**
     * Check if the current stackPtr is a use token that is for a closure.
     *
     * @param \PHP_CodeSniffer_File $phpcsFile
     * @param int $stackPtr
     *
     * @return bool
     */
    protected function _isClosure(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        return $phpcsFile->findPrevious(
            [T_CLOSURE],
            ($stackPtr - 1),
            null,
            false,
            null,
            true
        );
    }

}
