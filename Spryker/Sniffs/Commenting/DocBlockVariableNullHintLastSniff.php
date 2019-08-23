<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Ensures Doc Blocks for class properties contains the nullable type hint last.
 *
 * @author Mark Scherer, Ehsan Zanjani, Karoly Gerner
 * @license MIT
 */
class DocBlockVariableNullHintLastSniff extends AbstractSprykerSniff
{
    /**
     * @inheritDoc
     */
    public function register()
    {
        return [
            T_VARIABLE,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        $tokens = $phpCsFile->getTokens();

        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);
        if (!$docBlockEndIndex) {
            return;
        }
        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }

            if ($tokens[$i]['content'] === '@var') {
                $this->validateVarTypeHint($phpCsFile, $i, $docBlockEndIndex, $tokens);
                break;
            }
        }

    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $varCommentTagIndex
     * @param int $docBlockEndIndex
     *
     * @return void
     */
    protected function validateVarTypeHint(File $phpCsFile, int $varCommentTagIndex, int $docBlockEndIndex, array $tokens): void
    {
        $commentStringIndex = $phpCsFile->findNext(T_DOC_COMMENT_STRING, $varCommentTagIndex, $docBlockEndIndex);
        if (!$commentStringIndex) {
            return;
        }

        $wrongOrderCommentStringValue = $tokens[$commentStringIndex]['content'];
        if (!preg_match('/null\|/', $wrongOrderCommentStringValue)) {
            return;
        }

        $correctedOrderCommentStringValue = str_replace('null|', '', $wrongOrderCommentStringValue). '|null';
        $this->handleMissingVar($phpCsFile, $docBlockEndIndex, $commentStringIndex, $wrongOrderCommentStringValue, $correctedOrderCommentStringValue);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockStartIndex
     * @param string $wrongOrderCommentStringValue
     * @param string $correctedOrderCommentStringValue
     *
     * @return void
     */
    protected function handleMissingVar(File $phpCsFile, int $docBlockEndIndex, int $commentStringIndex, ?string $wrongOrderCommentStringValue, string $correctedOrderCommentStringValue): void
    {
        $error = 'Doc Block annotation @var for variable null is in wrong order';
        if ($wrongOrderCommentStringValue === null) {
            $phpCsFile->addError($error, $docBlockEndIndex, 'DocBlockWrongOrder');

            return;
        }

        $error .= ', type `' . $wrongOrderCommentStringValue . '` detected';
        $fix = $phpCsFile->addFixableError($error, $docBlockEndIndex, 'WrongType');
        if (!$fix) {
            return;
        }

        $phpCsFile->fixer->beginChangeset();
        $phpCsFile->fixer->replaceToken($commentStringIndex, $correctedOrderCommentStringValue);
        $phpCsFile->fixer->endChangeset();
    }
}
