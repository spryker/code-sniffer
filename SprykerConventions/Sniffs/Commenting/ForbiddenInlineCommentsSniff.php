<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerConventions\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Comments must not carry issue-tracker keys; inline comments (`//`, `#`, `/* *\/`) must not be
 * section banners or closing-brace markers either. Doc comments are checked as a whole, annotations
 * included, which Slevomat's ForbiddenComments sniff does not do: it stops at the first annotation.
 */
class ForbiddenInlineCommentsSniff extends AbstractSprykerSniff
{
    protected const string CODE_ISSUE_KEY = 'IssueKey';

    protected const string CODE_SECTION_BANNER = 'SectionBanner';

    protected const string CODE_CLOSING_BRACE_MARKER = 'ClosingBraceMarker';

    protected const string MESSAGE_ISSUE_KEY = 'Comment contains the issue key "%s"; issue keys belong in the commit message.';

    protected const string MESSAGE_SECTION_BANNER = 'Inline comment is a section banner; extract a method or class instead.';

    protected const string MESSAGE_CLOSING_BRACE_MARKER = 'Inline comment marks a closing brace; extract a method if the block is too long to follow.';

    /**
     * Uppercase identifiers of the issue-key shape that are standards, not tickets.
     *
     * @var array<string>
     */
    protected const array NON_ISSUE_KEY_PREFIXES = ['SHA', 'ISO', 'RFC', 'UTF', 'CVE', 'PSR'];

    protected const string PATTERN_ISSUE_KEY = '/\b([A-Z][A-Z0-9]{1,9})-[0-9]{3,6}\b/';

    protected const string PATTERN_COMMENT_MARKER = '#^(?://|\#|/\*|\*)#';

    protected const string PATTERN_SECTION_BANNER = '#^[-=*\#/+_~>]{3,}#';

    protected const string PATTERN_CLOSING_BRACE_MARKER = '/^end\s*(?:if|else|foreach|for|while|switch|class|function|method)\b/i';

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_COMMENT, T_DOC_COMMENT_STRING];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $token = $phpcsFile->getTokens()[$stackPtr];
        $text = $this->getCommentText($token['content']);
        if ($text === '') {
            return;
        }

        $issueKey = $this->findIssueKey($text);
        if ($issueKey !== null) {
            $phpcsFile->addError(static::MESSAGE_ISSUE_KEY, $stackPtr, static::CODE_ISSUE_KEY, [$issueKey]);
        }

        if ($token['code'] === T_DOC_COMMENT_STRING) {
            return;
        }

        if (preg_match(static::PATTERN_SECTION_BANNER, $text)) {
            $phpcsFile->addError(static::MESSAGE_SECTION_BANNER, $stackPtr, static::CODE_SECTION_BANNER);
        }

        if (preg_match(static::PATTERN_CLOSING_BRACE_MARKER, $text)) {
            $phpcsFile->addError(static::MESSAGE_CLOSING_BRACE_MARKER, $stackPtr, static::CODE_CLOSING_BRACE_MARKER);
        }
    }

    protected function getCommentText(string $content): string
    {
        return trim((string)preg_replace(static::PATTERN_COMMENT_MARKER, '', trim($content)));
    }

    protected function findIssueKey(string $text): ?string
    {
        preg_match_all(static::PATTERN_ISSUE_KEY, $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (!in_array($match[1], static::NON_ISSUE_KEY_PREFIXES, true)) {
                return $match[0];
            }
        }

        return null;
    }
}
