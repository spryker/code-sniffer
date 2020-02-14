<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
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
    public function register(): array
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
     * @param array $tokens
     *
     * @return void
     */
    protected function validateVarTypeHint(
        File $phpCsFile,
        int $varCommentTagIndex,
        int $docBlockEndIndex,
        array $tokens
    ): void {
        $commentStringIndex = $phpCsFile->findNext(T_DOC_COMMENT_STRING, $varCommentTagIndex, $docBlockEndIndex);
        if (!$commentStringIndex) {
            return;
        }

        $content = $tokens[$commentStringIndex]['content'];
        $appendix = '';
        $spacePos = strpos($content, ' ');
        if ($spacePos) {
            $appendix = substr($content, $spacePos);
            $content = substr($content, 0, $spacePos);
        }

        if (!preg_match('/null\|/', $content)) {
            return;
        }

        $this->handleInvalidOrder($phpCsFile, $docBlockEndIndex, $commentStringIndex, $content, $appendix);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockEndIndex
     * @param int $commentStringIndex
     * @param string $content
     * @param string $appendix
     *
     * @return void
     */
    protected function handleInvalidOrder(
        File $phpCsFile,
        int $docBlockEndIndex,
        int $commentStringIndex,
        string $content,
        string $appendix
    ): void {
        $content = str_replace('null|', '', $content) . '|null';
        $content = implode('|', array_unique(explode('|', $content)));
        if ($appendix) {
            $content .= $appendix;
        }

        $error = 'Doc Block annotation @var for type `null` has a wrong order';
        $error .= ', `' . $content . '` expected.';
        $fix = $phpCsFile->addFixableError($error, $docBlockEndIndex, 'WrongType');
        if (!$fix) {
            return;
        }

        $phpCsFile->fixer->beginChangeset();
        $phpCsFile->fixer->replaceToken($commentStringIndex, $content);
        $phpCsFile->fixer->endChangeset();
    }
}
