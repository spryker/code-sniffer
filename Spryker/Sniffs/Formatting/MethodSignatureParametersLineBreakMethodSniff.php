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
    public $methodSignatureLengthHardBreak = 160;

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
            if (
                $signatureLength <= $this->methodSignatureLengthHardBreak
                || $parametersCount === 0
            ) {
                return;
            }

            $fix = $phpcsFile->addFixableError('The parameters on this method definition need to be multi-line.', $stackPtr, 'Multiline');
            if (!$fix) {
                return;
            }

            $this->makeMethodSignatureMultiline($phpcsFile, $stackPtr);

            return;
        }

        //multiline allowed when signature is longer than the soft break
        if ($signatureLength >= $this->methodSignatureLengthSoftBreak) {
            return;
        }
        //multiline allowed if parameter count is too high.
        if (
            $parametersCount >= $this->methodSignatureNumberParameterSoftBreak
        ) {
            return;
        }
        $fix = $phpcsFile->addFixableError('The parameters on this method definition need to be on a single line.', $stackPtr, 'Inline');
        if ($fix) {
            return;
        }

        $this->makeMethodSignatureSingleLine($phpcsFile, $stackPtr);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function makeMethodSignatureSingleLine(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $openParenthesisPosition = $tokens[$stackPtr]['parenthesis_opener'];
        $closeParenthesisPosition = $tokens[$stackPtr]['parenthesis_closer'];
        //if null, it's an interface or abstract method.
        $scopeOpenerPosition = $tokens[$stackPtr]['scope_opener'] ?? null;
        $parameters = $phpcsFile->getMethodParameters($stackPtr);
        $properties = $phpcsFile->getMethodProperties($stackPtr);
        $returnTypePosition = $properties['return_type_token'];
        $indentation = $this->getIndentationWhitespace($phpcsFile, $stackPtr);

        $content = [];
        foreach ($parameters as $parameter) {
            $content[] = $parameter['content'];
        }
        $formattedParameters = implode(', ', $content);

        $phpcsFile->fixer->beginChangeset();
        if ($scopeOpenerPosition !== null) {
            $this->removeEverythingBetweenPositions($phpcsFile, $closeParenthesisPosition, $scopeOpenerPosition);
            $phpcsFile->fixer->addContentBefore($scopeOpenerPosition, "\n" . $indentation);
            if ($returnTypePosition !== false) {
                $phpcsFile->fixer->addContent($closeParenthesisPosition, ': ' . $tokens[$returnTypePosition]['content']);
            }
        }
        $this->removeEverythingBetweenPositions($phpcsFile, $openParenthesisPosition, $closeParenthesisPosition);
        $phpcsFile->fixer->addContentBefore($closeParenthesisPosition, $formattedParameters);
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function makeMethodSignatureMultiline(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $openParenthesisPosition = $tokens[$stackPtr]['parenthesis_opener'];
        $closeParenthesisPosition = $tokens[$stackPtr]['parenthesis_closer'];
        //if null, it's an interface or abstract method.
        $scopeOpenerPosition = $tokens[$stackPtr]['scope_opener'] ?? null;

        $parameters = $phpcsFile->getMethodParameters($stackPtr);

        $formattedParameters = "\n";
        $parameterContent = [];
        $indentation = $this->getIndentationWhitespace($phpcsFile, $stackPtr);
        foreach ($parameters as $parameter) {
            $parameterContent[] = str_repeat($indentation, 2) . $parameter['content'];
        }
        $formattedParameters .= implode(",\n", $parameterContent);
        $formattedParameters .= "\n$indentation";

        $phpcsFile->fixer->beginChangeset();
        $this->removeEverythingBetweenPositions($phpcsFile, $openParenthesisPosition, $closeParenthesisPosition);
        $phpcsFile->fixer->addContentBefore($closeParenthesisPosition, $formattedParameters);
        if ($scopeOpenerPosition !== null) {
            if (!$this->areTokensOnTheSameLine($tokens, $closeParenthesisPosition, $scopeOpenerPosition)) {
                $endOfPreviousLine = $this->getLineEndingPosition($tokens, $closeParenthesisPosition);
                $this->removeEverythingBetweenPositions($phpcsFile, $endOfPreviousLine - 1, $scopeOpenerPosition);
                $phpcsFile->fixer->addContentBefore($scopeOpenerPosition, ' ');
            }
        }
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $fromPosition
     * @param int $toPosition
     *
     * @return void
     */
    protected function removeEverythingBetweenPositions(File $phpcsFile, int $fromPosition, int $toPosition): void
    {
        for ($i = $fromPosition + 1; $i < $toPosition; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }
    }
}
