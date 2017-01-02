<?php
namespace Spryker\Sniffs\Formatting;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Sniff;
use PHP_CodeSniffer_Tokens;

/**
 * @category PHP
 * @package PHP_CodeSniffer
 * @author Greg Sherwood <gsherwood@squiz.net>
 * @author Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link http://pear.php.net/package/PHP_CodeSniffer
 *
 * @modified by Mark Scherer with some minor fixes and removal of error-prone parts
 */
class ArrayDeclarationSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * @inheritdoc
     */
    public function register()
    {
        return [
            T_OPEN_SHORT_ARRAY,
        ];
    }

    /**
     * @inheritdoc
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $arrayStart = $stackPtr;
        $arrayEnd = $tokens[$stackPtr]['bracket_closer'];

        // Check for empty arrays.
        $content = $phpcsFile->findNext(T_WHITESPACE, ($arrayStart + 1), ($arrayEnd + 1), true);
        if ($content === $arrayEnd) {
            return;
        }

        if ($tokens[$arrayStart]['line'] === $tokens[$arrayEnd]['line']) {
            $this->processSingleLineArray($phpcsFile, $arrayStart, $arrayEnd);
        } else {
            $this->processMultiLineArray($phpcsFile, $stackPtr, $arrayStart, $arrayEnd);
        }
    }

    /**
     * Processes a single-line array definition.
     *
     * @param \PHP_CodeSniffer_File $phpcsFile The current file being checked.
     * @param int $arrayStart The token that starts the array definition.
     * @param int $arrayEnd The token that ends the array definition.
     *
     * @return void
     */
    public function processSingleLineArray(PHP_CodeSniffer_File $phpcsFile, $arrayStart, $arrayEnd)
    {
        $tokens = $phpcsFile->getTokens();

        // Check if there are multiple values. If so, then it has to be multiple lines
        // unless it is contained inside a function call or condition.
        $valueCount = 0;
        $commas = [];
        for ($i = ($arrayStart + 1); $i < $arrayEnd; $i++) {
            // Skip bracketed statements, like function calls.
            if ($tokens[$i]['code'] === T_OPEN_PARENTHESIS) {
                $i = $tokens[$i]['parenthesis_closer'];
                continue;
            }

            if ($tokens[$i]['code'] === T_COMMA) {
                // Before counting this comma, make sure we are not
                // at the end of the array.
                $next = $phpcsFile->findNext(T_WHITESPACE, ($i + 1), $arrayEnd, true);
                if ($next !== false) {
                    $valueCount++;
                    $commas[] = $i;
                } else {
                    // There is a comma at the end of a single line array.
                    $error = 'Comma not allowed after last value in single-line array declaration';
                    $fix = $phpcsFile->addFixableError($error, $i, 'CommaAfterLast');
                    if ($fix === true) {
                        $phpcsFile->fixer->beginChangeset();

                        for ($j = $i; $j < $arrayEnd; $j++) {
                            $phpcsFile->fixer->replaceToken($j, '');
                        }

                        $phpcsFile->fixer->endChangeset();
                    }
                }
            }
        }
    }

    /**
     * Processes a multi-line array definition.
     *
     * @param \PHP_CodeSniffer_File $phpcsFile The current file being checked.
     * @param int $stackPtr The position of the current token in the stack passed in $tokens.
     * @param int $arrayStart The token that starts the array definition.
     * @param int $arrayEnd The token that ends the array definition.
     *
     * @return void
     */
    public function processMultiLineArray(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $arrayStart, $arrayEnd)
    {
        $tokens = $phpcsFile->getTokens();
        $keywordStart = $tokens[$stackPtr]['column'];

        $keyUsed = false;
        $singleUsed = false;
        $indices = [];
        $maxLength = 0;

        if ($tokens[$stackPtr]['code'] === T_ARRAY) {
            $lastToken = $tokens[$stackPtr]['parenthesis_opener'];
        } else {
            $lastToken = $stackPtr;
        }

        // Find all the double arrows that reside in this scope.
        for ($nextToken = ($stackPtr + 1); $nextToken < $arrayEnd; $nextToken++) {
            // Skip bracketed statements, like function calls.
            if ($tokens[$nextToken]['code'] === T_OPEN_PARENTHESIS
                && (isset($tokens[$nextToken]['parenthesis_owner']) === false
                    || $tokens[$nextToken]['parenthesis_owner'] !== $stackPtr)
            ) {
                $nextToken = $tokens[$nextToken]['parenthesis_closer'];
                continue;
            }

            if ($tokens[$nextToken]['code'] === T_ARRAY) {
                // Let subsequent calls of this test handle nested arrays.
                if ($tokens[$lastToken]['code'] !== T_DOUBLE_ARROW) {
                    $indices[] = ['value' => $nextToken];
                    $lastToken = $nextToken;
                }

                $nextToken = $tokens[$tokens[$nextToken]['parenthesis_opener']]['parenthesis_closer'];
                $nextToken = $phpcsFile->findNext(T_WHITESPACE, ($nextToken + 1), null, true);
                if ($tokens[$nextToken]['code'] !== T_COMMA) {
                    $nextToken--;
                } else {
                    $lastToken = $nextToken;
                }

                continue;
            }

            if ($tokens[$nextToken]['code'] === T_OPEN_SHORT_ARRAY) {
                // Let subsequent calls of this test handle nested arrays.
                if ($tokens[$lastToken]['code'] !== T_DOUBLE_ARROW) {
                    $indices[] = ['value' => $nextToken];
                    $lastToken = $nextToken;
                }

                $nextToken = $tokens[$nextToken]['bracket_closer'];
                $nextToken = $phpcsFile->findNext(T_WHITESPACE, ($nextToken + 1), null, true);
                if ($tokens[$nextToken]['code'] !== T_COMMA) {
                    $nextToken--;
                } else {
                    $lastToken = $nextToken;
                }

                continue;
            }

            if ($tokens[$nextToken]['code'] === T_CLOSURE) {
                if ($tokens[$lastToken]['code'] !== T_DOUBLE_ARROW) {
                    $indices[] = ['value' => $nextToken];
                    $lastToken = $nextToken;
                }

                $nextToken = $tokens[$nextToken]['scope_closer'];
                $nextToken = $phpcsFile->findNext(T_WHITESPACE, ($nextToken + 1), null, true);
                if ($tokens[$nextToken]['code'] !== T_COMMA) {
                    $nextToken--;
                } else {
                    $lastToken = $nextToken;
                }

                continue;
            }

            if ($tokens[$nextToken]['code'] !== T_DOUBLE_ARROW
                && $tokens[$nextToken]['code'] !== T_COMMA
            ) {
                continue;
            }

            $currentEntry = [];

            if ($tokens[$nextToken]['code'] === T_COMMA) {
                $stackPtrCount = 0;
                if (isset($tokens[$stackPtr]['nested_parenthesis']) === true) {
                    $stackPtrCount = count($tokens[$stackPtr]['nested_parenthesis']);
                }

                $commaCount = 0;
                if (isset($tokens[$nextToken]['nested_parenthesis']) === true) {
                    $commaCount = count($tokens[$nextToken]['nested_parenthesis']);
                    if ($tokens[$stackPtr]['code'] === T_ARRAY) {
                        // Remove parenthesis that are used to define the array.
                        $commaCount--;
                    }
                }

                if ($commaCount > $stackPtrCount) {
                    // This comma is inside more parenthesis than the ARRAY keyword,
                    // then there it is actually a comma used to separate arguments
                    // in a function call.
                    continue;
                }

                if ($keyUsed === true && $tokens[$lastToken]['code'] === T_COMMA) {
                    return;
                }

                if ($keyUsed === false) {
                    if ($tokens[($nextToken - 1)]['code'] === T_WHITESPACE) {
                        $content = $tokens[($nextToken - 2)]['content'];
                        if ($tokens[($nextToken - 1)]['content'] === $phpcsFile->eolChar) {
                            $spaceLength = 'newline';
                        } else {
                            $spaceLength = $tokens[($nextToken - 1)]['length'];
                        }
                    }

                    $valueContent = $phpcsFile->findNext(
                        PHP_CodeSniffer_Tokens::$emptyTokens,
                        ($lastToken + 1),
                        $nextToken,
                        true
                    );

                    $indices[] = ['value' => $valueContent];
                    $singleUsed = true;
                }

                $lastToken = $nextToken;
                continue;
            }

            if ($tokens[$nextToken]['code'] === T_DOUBLE_ARROW) {
                $currentEntry['arrow'] = $nextToken;
                $keyUsed = true;

                // Find the start of index that uses this double arrow.
                $indexEnd = $phpcsFile->findPrevious(T_WHITESPACE, ($nextToken - 1), $arrayStart, true);
                $indexStart = $phpcsFile->findStartOfStatement($indexEnd);

                if ($indexStart === $indexEnd) {
                    $currentEntry['index'] = $indexEnd;
                    $currentEntry['index_content'] = $tokens[$indexEnd]['content'];
                } else {
                    $currentEntry['index'] = $indexStart;
                    $currentEntry['index_content'] = $phpcsFile->getTokensAsString($indexStart, ($indexEnd - $indexStart + 1));
                }

                $indexLength = strlen($currentEntry['index_content']);
                if ($maxLength < $indexLength) {
                    $maxLength = $indexLength;
                }

                // Find the value of this index.
                $nextContent = $phpcsFile->findNext(
                    PHP_CodeSniffer_Tokens::$emptyTokens,
                    ($nextToken + 1),
                    $arrayEnd,
                    true
                );

                $currentEntry['value'] = $nextContent;
                $indices[] = $currentEntry;
                $lastToken = $nextToken;
            }
        }

        if ($keyUsed === false && empty($indices) === false) {
            $count = count($indices);
            $lastIndex = $indices[($count - 1)]['value'];

            $trailingContent = $phpcsFile->findPrevious(
                PHP_CodeSniffer_Tokens::$emptyTokens,
                ($arrayEnd - 1),
                $lastIndex,
                true
            );

            if ($tokens[$trailingContent]['code'] !== T_COMMA) {
                $phpcsFile->recordMetric($stackPtr, 'Array end comma', 'no');
                $error = 'Comma required after last value in array declaration';
                $fix = $phpcsFile->addFixableError($error, $trailingContent, 'NoCommaAfterLast');
                if ($fix === true) {
                    $phpcsFile->fixer->addContent($trailingContent, ',');
                }
            } else {
                $phpcsFile->recordMetric($stackPtr, 'Array end comma', 'yes');
            }

            $lastValueLine = false;
            foreach ($indices as $value) {
                if (empty($value['value']) === true) {
                    // Array was malformed and we couldn't figure out
                    // the array value correctly, so we have to ignore it.
                    // Other parts of this sniff will correct the error.
                    continue;
                }

                $lastValueLine = $tokens[$value['value']]['line'];
            }
        }

        /*
            Below the actual indentation of the array is checked.
            Errors will be thrown when a key is not aligned, when
            a double arrow is not aligned, and when a value is not
            aligned correctly.
            If an error is found in one of the above areas, then errors
            are not reported for the rest of the line to avoid reporting
            spaces and columns incorrectly. Often fixing the first
            problem will fix the other 2 anyway.

            For example:

            $a = array(
                  'index'  => '2',
                 );

            or

            $a = [
                  'index'  => '2',
                 ];

            In this array, the double arrow is indented too far, but this
            will also cause an error in the value's alignment. If the arrow were
            to be moved back one space however, then both errors would be fixed.
        */

        $numValues = count($indices);

        $indicesStart = ($keywordStart + 1);
        $arrowStart = ($indicesStart + $maxLength + 1);
        foreach ($indices as $index) {
            if (isset($index['index']) === false) {
                // Array value only.
                if ($tokens[$index['value']]['line'] === $tokens[$stackPtr]['line'] && $numValues > 1) {
                    $error = 'The first value in a multi-value array must be on a new line';
                    $fix = $phpcsFile->addFixableError($error, $stackPtr, 'FirstValueNoNewline');
                    if ($fix === true) {
                        $phpcsFile->fixer->addNewlineBefore($index['value']);
                    }
                }

                continue;
            }

            $indexLine = $tokens[$index['index']]['line'];

            if ($indexLine === $tokens[$stackPtr]['line']) {
                $error = 'The first index in a multi-value array must be on a new line';
                $fix = $phpcsFile->addFixableError($error, $index['index'], 'FirstIndexNoNewline');
                if ($fix === true) {
                    $phpcsFile->fixer->addNewlineBefore($index['index']);
                }

                continue;
            }

            // Check each line ends in a comma.
            $valueLine = $tokens[$index['value']]['line'];
            $nextComma = false;
            for ($i = $index['value']; $i < $arrayEnd; $i++) {
                // Skip bracketed statements, like function calls.
                if ($tokens[$i]['code'] === T_OPEN_PARENTHESIS) {
                    $i = $tokens[$i]['parenthesis_closer'];
                    $valueLine = $tokens[$i]['line'];
                    continue;
                }

                if ($tokens[$i]['code'] === T_ARRAY) {
                    $i = $tokens[$tokens[$i]['parenthesis_opener']]['parenthesis_closer'];
                    $valueLine = $tokens[$i]['line'];
                    continue;
                }

                // Skip to the end of multi-line strings.
                if (isset(PHP_CodeSniffer_Tokens::$stringTokens[$tokens[$i]['code']]) === true) {
                    $i = $phpcsFile->findNext($tokens[$i]['code'], ($i + 1), null, true);
                    $i--;
                    $valueLine = $tokens[$i]['line'];
                    continue;
                }

                if ($tokens[$i]['code'] === T_OPEN_SHORT_ARRAY) {
                    $i = $tokens[$i]['bracket_closer'];
                    $valueLine = $tokens[$i]['line'];
                    continue;
                }

                if ($tokens[$i]['code'] === T_CLOSURE) {
                    $i = $tokens[$i]['scope_closer'];
                    $valueLine = $tokens[$i]['line'];
                    continue;
                }

                if ($tokens[$i]['code'] === T_COMMA) {
                    $nextComma = $i;
                    break;
                }
            }

            if ($nextComma === false || ($tokens[$nextComma]['line'] !== $valueLine)) {
                $error = 'Each line in a multi-line array declaration must end in a comma';
                $fix = $phpcsFile->addFixableError($error, $index['value'], 'NoComma');

                if ($fix === true) {
                    // Find the end of the line and put a comma there.
                    for ($i = ($index['value'] + 1); $i < $arrayEnd; $i++) {
                        if ($tokens[$i]['line'] > $valueLine) {
                            break;
                        }
                    }

                    $phpcsFile->fixer->addContentBefore(($i - 1), ',');
                }
            }
        }
    }

}
