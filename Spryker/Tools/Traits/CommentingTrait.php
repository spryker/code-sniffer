<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Tools\Traits;

use PHP_CodeSniffer\Files\File;

/**
 * Common functionality around commenting.
 */
trait CommentingTrait
{
    /**
     * Looks for either `@inheritDoc` or `{@inheritDoc}`.
     * Also allows `@inheritdoc` or `{@inheritdoc}` aliases.
     *
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     * @param string $needle
     *
     * @return bool
     */
    protected function hasInheritDoc(File $phpCsFile, $docBlockStartIndex, $docBlockEndIndex, $needle = '@inheritDoc')
    {
        $tokens = $phpCsFile->getTokens();

        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; ++$i) {
            if (empty($tokens[$i]['content'])) {
                continue;
            }
            $content = $tokens[$i]['content'];
            $pos = stripos($content, $needle);
            if ($pos === false) {
                continue;
            }

            if ($pos && strpos($needle, '@') === 0 && substr($content, $pos - 1, $pos) === '{') {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Allow \Foo\Bar[] or array<\Foo\Bar> to pass as array.
     *
     * @param string[] $docBlockTypes
     *
     * @return bool
     */
    protected function containsTypeArray(array $docBlockTypes): bool
    {
        foreach ($docBlockTypes as $docBlockType) {
            if (strpos($docBlockType, '[]') !== false || strpos($docBlockType, 'array<') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks for ...<...>.
     *
     * @param string[] $docBlockTypes
     *
     * @return bool
     */
    protected function containsIterableSyntax(array $docBlockTypes): bool
    {
        foreach ($docBlockTypes as $docBlockType) {
            if (strpos($docBlockType, '<') !== false) {
                return true;
            }
        }

        return false;
    }
}
