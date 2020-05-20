<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use Spryker\Tools\Traits\CommentingTrait;

/**
 * No invalid array/iterable tags used, and proper whitespace rules.
 * Basically type<x, y>. Also nesting, e.g. type<x, <type2<y, z>>.
 */
class DocBlockTagIterableSniff implements Sniff
{
    use CommentingTrait;

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
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $content = $tokens[$stackPtr]['content'];

        $tag = $content;
        if (strpos($tag, ' ') !== false) {
            [$tag, $description] = explode(' ', $content, 2);
        }

        if (!preg_match('#^@(?:[a-z]+-)?(?:param|return|var)\b#i', $tag)) {
            return;
        }

        $possibleTextIndex = $phpcsFile->findNext(T_DOC_COMMENT_STRING, $stackPtr + 1, $stackPtr + 3, false, null, true);
        if (!$possibleTextIndex) {
            return;
        }

        $this->assertType($phpcsFile, $possibleTextIndex, $tag);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     * @param string $tag
     *
     * @return void
     */
    protected function assertType(File $phpcsFile, int $stackPtr, string $tag): void
    {
        $tokens = $phpcsFile->getTokens();

        $content = $tokens[$stackPtr]['content'];
        if (!$this->containsIterableSyntax([$content])) {
            return;
        }

        preg_match('#^(\w+)<(.+)>\s*(.*)$#', $content, $matches);
        if (!$matches) {
            return;
        }

        $type = $matches[1];
        $definition = $matches[2];
        $appendix = $matches[3];

        $correctDefinition = $this->assertDefinition($definition);

        if ($definition === $correctDefinition) {
            return;
        }

        $message = sprintf('Definition of tag `%s` is not expected `%s`, but `%s`.', $tag, $correctDefinition, $definition);
        $fix = $phpcsFile->addFixableError($message, $stackPtr, 'Definition');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();

        $fixedTag = $type . '<' . $correctDefinition . '>';
        if ($appendix) {
            $fixedTag .= ' ' . trim($appendix);
        }

        $phpcsFile->fixer->replaceToken($stackPtr, $fixedTag);

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param string $definition
     *
     * @return string
     */
    protected function assertDefinition(string $definition): string
    {
        return preg_replace_callback('#,([^ ])#', function ($matches) {
            return ', ' . $matches[1];
        }, $definition);
    }
}
