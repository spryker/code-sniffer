<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * In method return types self for chaining methods is disallowed as it is poorly supported by the language.
 *
 * @author Mark Scherer
 */
class ReturnTypeHintSniff extends AbstractSprykerSniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_FUNCTION];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $openParenthesisIndex = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr + 1);
        $closeParenthesisIndex = $tokens[$openParenthesisIndex]['parenthesis_closer'];

        $colonIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $closeParenthesisIndex + 1, null, true);
        if (!$colonIndex) {
            return;
        }

        $startIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $colonIndex + 1, $colonIndex + 3, true);
        if (!$startIndex) {
            return;
        }

        if (!$this->isChainingMethod($phpcsFile, $stackPtr)) {
            $this->assertNotThisOrStatic($phpcsFile, $stackPtr);

            return;
        }

        // We skip for interface methods
        if (empty($tokens[$stackPtr]['scope_opener']) || empty($tokens[$stackPtr]['scope_closer'])) {
            return;
        }

        $returnTokenType = $tokens[$startIndex]['type'];
        if ($returnTokenType !== 'T_SELF') {
            // Then we can only warn, but not auto-fix
            $phpcsFile->addError('Chaining methods (@return $this) should not have any return-type-hint.', $startIndex, 'TypeHint.Invalid.Self');

            return;
        }

        $fix = $phpcsFile->addFixableError('Chaining methods (@return $this) should not have any return-type-hint (Remove "self").', $startIndex, 'TypeHint.Invalid.Self');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        for ($i = $colonIndex; $i <= $startIndex; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isChainingMethod(File $phpCsFile, int $stackPointer): bool
    {
        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);

        if (!$docBlockEndIndex) {
            return false;
        }

        $tokens = $phpCsFile->getTokens();

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if ($tokens[$i]['content'] !== '@return') {
                continue;
            }

            $classNameIndex = $i + 2;

            if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
                continue;
            }

            $content = $tokens[$classNameIndex]['content'];
            if (!$content) {
                continue;
            }

            return $content === '$this';
        }

        return false;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function assertNotThisOrStatic(File $phpCsFile, int $stackPointer): void
    {
        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);

        if (!$docBlockEndIndex) {
            return;
        }

        $tokens = $phpCsFile->getTokens();

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if ($tokens[$i]['content'] !== '@return') {
                continue;
            }

            $classNameIndex = $i + 2;

            if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
                continue;
            }

            $content = $tokens[$classNameIndex]['content'];
            if (!$content || strpos($content, '\\') !== 0) {
                continue;
            }

            $classNameWithNamespace = $this->getClassNameWithNamespace($phpCsFile);
            if ($content !== $classNameWithNamespace) {
                continue;
            }

            $phpCsFile->addError('Class name repeated, expected `self` or `$this`.', $classNameIndex, 'TypeHint.Invalid.Class');
        }
    }
}
