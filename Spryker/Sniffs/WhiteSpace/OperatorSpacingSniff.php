<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Verifies that operators have valid spacing surrounding them.
 *
 * @author Mark Scherer
 * @license MIT
 */
class OperatorSpacingSniff extends AbstractSprykerSniff
{
    /**
     * @var array<string>
     */
    public $supportedTokenizers = [
        'PHP',
        'JS',
    ];

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        $comparison = Tokens::$comparisonTokens;
        $operators = Tokens::$operators;
        $assignment = Tokens::$assignmentTokens;

        return array_unique(array_merge($comparison, $operators, $assignment));
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        // Skip default values in function declarations.
        if (
            $tokens[$stackPtr]['code'] === T_EQUAL
            || $tokens[$stackPtr]['code'] === T_MINUS
        ) {
            if (isset($tokens[$stackPtr]['nested_parenthesis']) === true) {
                $parenthesis = array_keys($tokens[$stackPtr]['nested_parenthesis']);
                $bracket = array_pop($parenthesis);
                if (isset($tokens[$bracket]['parenthesis_owner']) === true) {
                    $function = $tokens[$bracket]['parenthesis_owner'];
                    if ($tokens[$function]['code'] === T_FUNCTION) {
                        return;
                    }
                }
            }
        }

        if ($tokens[$stackPtr]['code'] === T_EQUAL) {
            /*
            // Skip for '=&' case.
            if (isset($tokens[($stackPtr + 1)]) === true && $tokens[($stackPtr + 1)]['code'] === T_BITWISE_AND) {
                return;
            }
            */
        }

        if ($tokens[$stackPtr]['code'] === T_BITWISE_AND) {
            // If its not a reference, then we expect one space either side of the
            // bitwise operator.
            if (!$phpcsFile->isReference($stackPtr)) {
                // Check there is one space before the & operator.
                if ($tokens[($stackPtr - 1)]['code'] !== T_WHITESPACE) {
                    $error = 'Expected 1 space before "&" operator; 0 found';
                    $fix = $phpcsFile->addFixableError($error, $stackPtr, 'NoSpaceBeforeAmp');
                    if ($fix) {
                        $phpcsFile->fixer->addContent($stackPtr - 1, ' ');
                    }
                }

                // Check there is one space after the & operator.
                if ($tokens[($stackPtr + 1)]['code'] !== T_WHITESPACE) {
                    $error = 'Expected 1 space after "&" operator; 0 found';
                    $fix = $phpcsFile->addFixableError($error, $stackPtr, 'NoSpaceAfterAmp');
                    if ($fix) {
                        $phpcsFile->fixer->addContent($stackPtr, ' ');
                    }
                }
            } else {
                if ($tokens[($stackPtr + 1)]['code'] === T_WHITESPACE) {
                    $error = 'Expected 0 spaces after reference operator';
                    $fix = $phpcsFile->addFixableError($error, $stackPtr, 'SpacingAfterReference');
                    if ($fix) {
                        $phpcsFile->fixer->replaceToken($stackPtr + 1, '');
                    }
                }
            }
        } else {
            if ($tokens[$stackPtr]['code'] === T_MINUS) {
                // Check minus spacing, but make sure we aren't just assigning
                // a minus value or returning one.
                $prev = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
                if ($tokens[$prev]['code'] === T_RETURN) {
                    // Just returning a negative value; eg. return -1.
                    return;
                }

                if (in_array($tokens[$prev]['code'], Tokens::$operators) === true) {
                    // Just trying to operate on a negative value; eg. ($var * -1).
                    return;
                }

                if (in_array($tokens[$prev]['code'], Tokens::$comparisonTokens) === true) {
                    // Just trying to compare a negative value; eg. ($var === -1).
                    return;
                }

                // A list of tokens that indicate that the token is not
                // part of an arithmetic operation.
                $invalidTokens = [
                    T_COMMA,
                    T_OPEN_PARENTHESIS,
                    T_OPEN_SQUARE_BRACKET,
                    T_OPEN_SHORT_ARRAY,
                    T_DOUBLE_ARROW,
                    T_COLON,
                    T_INLINE_THEN,
                    T_INLINE_ELSE,
                    T_CASE,
                ];

                if (in_array($tokens[$prev]['code'], $invalidTokens) === true) {
                    // Just trying to use a negative value; eg. myFunction($var, -2).
                    return;
                }
                if (in_array($tokens[$prev]['code'], Tokens::$assignmentTokens) === true) {
                    // Just trying to assign a negative value; eg. ($var = -1).
                    return;
                }
            }

            $operator = $tokens[$stackPtr]['content'];

            if ($tokens[($stackPtr - 1)]['code'] !== T_WHITESPACE) {
                // Taken care of other sniffer
            } elseif ($tokens[$stackPtr - 2]['line'] === $tokens[$stackPtr]['line']) {
                $content = $tokens[($stackPtr - 1)]['content'];
                if ($content !== ' ') {
                    $error = sprintf('Expected 1 space before "%s"; %s found', $operator, strlen($content));
                    $fix = $phpcsFile->addFixableError($error, $stackPtr, 'TooManySpacesBefore');
                    if ($fix) {
                        $phpcsFile->fixer->replaceToken($stackPtr - 1, ' ');
                    }
                }
            }

            if ($tokens[($stackPtr + 1)]['code'] !== T_WHITESPACE) {
                // Taken care of other sniffer
            } elseif ($tokens[$stackPtr + 2]['line'] === $tokens[$stackPtr]['line']) {
                $content = $tokens[($stackPtr + 1)]['content'];
                if ($content !== ' ') {
                    $error = sprintf('Expected 1 space after "%s"; %s found', $operator, strlen($content));
                    $fix = $phpcsFile->addFixableError($error, $stackPtr, 'TooManySpacesAfter');
                    if ($fix) {
                        $phpcsFile->fixer->replaceToken($stackPtr + 1, ' ');
                    }
                }
            }
        }
    }
}
