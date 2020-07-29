<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Verifies that a `@return` tag description does not start with $ sign to avoid accidental variable copy-and-paste.
 * Also checks duplicates.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockReturnTagSniff extends AbstractSprykerSniff
{
    /**
     * @inheritDoc
     */
    public function register()
    {
        return [T_DOC_COMMENT_TAG];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['content'] !== '@return') {
            return;
        }

        $nextIndex = $phpcsFile->findNext(T_DOC_COMMENT_STRING, $stackPtr + 1, $stackPtr + 3);
        if (!$nextIndex) {
            return;
        }

        $this->assertTypes($phpcsFile, $nextIndex);
        $this->assertDescription($phpcsFile, $nextIndex);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $nextIndex
     *
     * @return void
     */
    protected function assertTypes(File $phpcsFile, int $nextIndex): void
    {
        $tokens = $phpcsFile->getTokens();

        $content = $tokens[$nextIndex]['content'];
        $returnTypes = explode('|', $content);

        $unique = array_unique($returnTypes);
        if (count($unique) !== count($returnTypes)) {
            $after = implode('|', $unique);
            $fix = $phpcsFile->addFixableError(sprintf('Types are duplicate: `%s`, expected `%s`.', $content, $after), $nextIndex, 'DuplicateTypes');
            if ($fix) {
                $phpcsFile->fixer->replaceToken($nextIndex, $after);
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $nextIndex
     *
     * @return void
     */
    protected function assertDescription(File $phpcsFile, int $nextIndex): void
    {
        $tokens = $phpcsFile->getTokens();

        $content = $tokens[$nextIndex]['content'];
        if (strpos($content, ' ') === false) {
            return;
        }

        [$hint, $description] = explode(' ', $content, 2);
        if (!$description || substr($description, 0, 1) !== '$') {
            return;
        }

        $phpcsFile->addError('Description for return annotation must not start with `$`/variable. Use normal sentence instead.', $nextIndex, 'Invalid');
    }
}
