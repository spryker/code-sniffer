<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Formatting;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Prevent the usage of multiline for short method signatures and single lines for long ones.
 */
class MethodSignatureParametersLineBreakMethodSniff extends AbstractSprykerSniff
{
    /**
     * @var int
     */
    public $methodSignatureLengthHardBreak = 120;

    /**
     * @var int
     */
    public $methodSignatureLengthSoftBreak = 80;

    /**
     * @var int
     */
    public $methodSignatureNumberParameterSoftBreak = 3;

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
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $openParenthesisPosition = $tokens[$stackPtr]['parenthesis_opener'];
        $closeParenthesisPosition = $tokens[$stackPtr]['parenthesis_closer'];

        $isSingleLineSignature = $this->areTokensOnTheSameLine($tokens, $openParenthesisPosition, $closeParenthesisPosition);
        $signatureLength = $this->getMethodSignatureLength($phpcsFile, $stackPtr);
        $parametersCount = count($phpcsFile->getMethodParameters($stackPtr));
        if ($isSingleLineSignature) {
            //single line only allowed when the length don't go over the hard break or there are no parameters
            if ($signatureLength <= $this->methodSignatureLengthHardBreak
                || $parametersCount === 0
            ) {
                return;
            }
            $phpcsFile->addFixableError('The parameters on this method definition needs to be multi-line.', $stackPtr, 'multiline');
        }

        if (!$isSingleLineSignature) {
            //multiline allowed when signature is longer than the hard break
            if ($signatureLength >= $this->methodSignatureLengthHardBreak) {
                return;
            }
            //multiline allowed after the soft break if the number of parameters is too high.
            if ($signatureLength >= $this->methodSignatureLengthSoftBreak
                && $parametersCount >= $this->methodSignatureNumberParameterSoftBreak
            ) {
                return;
            }
            $phpcsFile->addFixableError('The parameters on this method definition needs to be on a single line.', $stackPtr, 'inline');
        }

        if ($phpcsFile->fixer->enabled === false) {
            return;
        }
        if ($isSingleLineSignature) {
            $this->makeMethodSignatureMultiline($phpcsFile, $stackPtr);

            return;
        }
        $this->makeMethodSignatureSingleLine($phpcsFile, $stackPtr);
    }
}
