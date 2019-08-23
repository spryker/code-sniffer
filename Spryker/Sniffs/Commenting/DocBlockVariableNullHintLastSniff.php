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
 * @author Mark Scherer
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
                $this->fixVarHint($phpCsFile, $i, $docBlockEndIndex);
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
    protected function fixVarHint(File $phpCsFile, int $varCommentTagIndex, int $docBlockEndIndex): void
    {
        $tokens = $phpCsFile->getTokens();


    }

    /**
     * @param array $token
     *
     * @return string|null
     */
    protected function detectType(array $token): ?string
    {
        if ($this->isGivenKind(T_OPEN_SHORT_ARRAY, $token)) {
            return 'array';
        }

        if ($this->isGivenKind(T_LNUMBER, $token)) {
            return 'int';
        }

        if ($this->isGivenKind(T_CONSTANT_ENCAPSED_STRING, $token)) {
            return 'string';
        }

        if ($this->isGivenKind([T_FALSE, T_TRUE], $token)) {
            return 'bool';
        }

        if ($this->isGivenKind(T_NULL, $token)) {
            return 'null';
        }

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockEndIndex
     * @param int $docBlockStartIndex
     * @param string|null $defaultValueType
     *
     * @return void
     */
    protected function handleMissingVar(File $phpCsFile, int $docBlockEndIndex, int $docBlockStartIndex, ?string $defaultValueType): void
    {
        $error = 'Doc Block annotation @var for variable missing';
        if ($defaultValueType === null) {
            $phpCsFile->addError($error, $docBlockEndIndex, 'DocBlockMissing');

            return;
        }

        $error .= ', type `' . $defaultValueType . '` detected';
        $fix = $phpCsFile->addFixableError($error, $docBlockEndIndex, 'WrongType');
        if (!$fix) {
            return;
        }

        $index = $phpCsFile->findPrevious(Tokens::$emptyTokens, $docBlockEndIndex - 1, $docBlockStartIndex, true);
        if (!$index) {
            $index = $docBlockStartIndex;
        }

        $phpCsFile->fixer->beginChangeset();
        $phpCsFile->fixer->addNewline($index);
        $phpCsFile->fixer->addContent($index, "\t" . ' * @var ' . $defaultValueType);
        $phpCsFile->fixer->endChangeset();
    }
}
