<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * No invalid tags used. Basically @ followed by text.
 * Also lowercase inheritdoc usage should be always canonical inheritDoc.
 */
class DocBlockTagSniff implements Sniff
{
    protected const string INHERIT_DOC_FULL = '@inheritDoc';

    protected const string INHERIT_DOC_FULL_INVALID = '@inheritdoc';

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_DOC_COMMENT_TAG,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $content = $tokens[$stackPtr]['content'];

        $description = '';
        $tag = $content;
        if (strpos($tag, ' ') !== false) {
            [$tag, $description] = explode(' ', $content, 2);
        }

        if (!preg_match('#^@[a-z]+.+$#i', $tag)) {
            $phpcsFile->addError('Invalid tag `' . $tag . '`', $stackPtr, 'Invalid');

            return;
        }

        $this->assertInheritDocCasing($phpcsFile, $stackPtr, $tag, $description);
    }

    protected function assertInheritDocCasing(File $phpcsFile, int $stackPtr, string $tag, string $description): void
    {
        if ($tag === static::INHERIT_DOC_FULL) {
            return;
        }

        $fixedTag = str_ireplace(static::INHERIT_DOC_FULL_INVALID, static::INHERIT_DOC_FULL, $tag);

        if ($fixedTag === $tag) {
            return;
        }

        $message = sprintf('Casing of tag `%s` is not expected casing `%s`.', $tag, static::INHERIT_DOC_FULL);
        $fix = $phpcsFile->addFixableWarning($message, $stackPtr, 'Casing');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();

        if ($description) {
            $fixedTag .= ' ' . $description;
        }

        $phpcsFile->fixer->replaceToken($stackPtr, $fixedTag);

        $phpcsFile->fixer->endChangeset();
    }
}
