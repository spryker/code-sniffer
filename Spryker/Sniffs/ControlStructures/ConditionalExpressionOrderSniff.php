<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use Spryker\Traits\BasicsTrait;

/**
 * Checks that no YODA conditions (reversed order of natural conditions) are being used.
 */
class ConditionalExpressionOrderSniff implements Sniff
{
    use BasicsTrait;

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return Tokens::$comparisonTokens;
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        $tokens = $phpCsFile->getTokens();

        $prevIndex = $phpCsFile->findPrevious(Tokens::$emptyTokens, ($stackPointer - 1), null, true);
        if (!in_array($tokens[$prevIndex]['code'], [T_TRUE, T_FALSE, T_NULL, T_LNUMBER, T_CONSTANT_ENCAPSED_STRING], true)) {
            return;
        }

        $prevIndex = $phpCsFile->findPrevious(Tokens::$emptyTokens, ($prevIndex - 1), null, true);
        if (!$prevIndex) {
            return;
        }
        if ($this->isGivenKind(Tokens::$arithmeticTokens, $tokens[$prevIndex])) {
            return;
        }
        if ($this->isGivenKind([T_STRING_CONCAT], $tokens[$prevIndex])) {
            return;
        }

        $error = 'Usage of Yoda conditions is not allowed. Switch the expression order.';
        $prevContent = $tokens[$prevIndex]['content'];

        if (
            !$this->isGivenKind(Tokens::$assignmentTokens, $tokens[$prevIndex])
            && !$this->isGivenKind(Tokens::$booleanOperators, $tokens[$prevIndex])
            && $prevContent !== '('
        ) {
            // Not fixable
            $phpCsFile->addError($error, $stackPointer, 'YodaNotAllowed');

            return;
        }

        //TODO
    }
}
