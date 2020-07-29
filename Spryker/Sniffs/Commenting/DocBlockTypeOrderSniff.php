<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Tools\Traits\CommentingTrait;
use Spryker\Tools\Traits\SignatureTrait;

/**
 * Makes sure doc block param/return types have the right order and do not duplicate.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockTypeOrderSniff extends AbstractSprykerSniff
{
    use CommentingTrait;
    use SignatureTrait;

    /**
     * Highest/First element will be last in list of param or return tag.
     *
     * @var string[]
     */
    protected $sortMap = [
        'void',
        'null',
        'false',
    ];

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
    public function process(File $phpCsFile, $stackPointer)
    {
        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);
        if (!$docBlockEndIndex) {
            return;
        }

        $tokens = $phpCsFile->getTokens();
        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        $docBlockParams = $this->getDocBlockParams($tokens, $docBlockStartIndex, $docBlockEndIndex);

        $this->assertOrder($phpCsFile, $docBlockParams);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param array $docBlockParams
     *
     * @return void
     */
    protected function assertOrder(File $phpCsFile, array $docBlockParams): void
    {
        foreach ($docBlockParams as $docBlockParam) {
            $docBlockParamTypes = explode('|', $docBlockParam['type']);
            if (count($docBlockParamTypes) === 1) {
                continue;
            }
            $unique = array_unique($docBlockParamTypes);
            if (count($docBlockParamTypes) !== count($unique)) {
                $phpCsFile->addError('Duplicate type in `' . $docBlockParam['type'] . '`', $docBlockParam['index'], 'Duplicate');

                continue;
            }
            $expectedOrder = $this->getExpectedOrder($docBlockParamTypes);
            if ($expectedOrder === $docBlockParamTypes) {
                continue;
            }

            $fix = $phpCsFile->addFixableError('`null` and falsey values should be the last element', $docBlockParam['index'], 'WrongOrder');
            if (!$fix) {
                continue;
            }

            $phpCsFile->fixer->beginChangeset();

            $content = implode('|', $expectedOrder) . $docBlockParam['appendix'];
            $phpCsFile->fixer->replaceToken($docBlockParam['index'], $content);

            $phpCsFile->fixer->endChangeset();
        }
    }

    /**
     * @uses DocBlockTypeOrderSniff::compare()
     *
     * @param string[] $elements
     *
     * @return string[]
     */
    protected function getExpectedOrder(array $elements): array
    {
        global $sortOrder;

        $sortOrder = array_reverse($this->sortMap);
        usort($elements, [$this, 'compare']);

        return $elements;
    }

    /**
     * @param string $a
     * @param string $b
     *
     * @return int
     */
    protected function compare(string $a, string $b): int
    {
        global $sortOrder;

        $aIndex = array_search($a, $sortOrder, true);
        $bIndex = array_search($b, $sortOrder, true);
        if ($aIndex === false) {
            return -1;
        }

        if ($bIndex === false) {
            return 1;
        }

        return $aIndex - $bIndex;
    }

    /**
     * @param array $tokens
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return array
     */
    protected function getDocBlockParams(array $tokens, int $docBlockStartIndex, int $docBlockEndIndex): array
    {
        $docBlockParams = [];
        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if (!in_array($tokens[$i]['content'], ['@param', '@return'], true)) {
                continue;
            }

            $classNameIndex = $i + 2;

            if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
                continue;
            }

            $content = $tokens[$classNameIndex]['content'];

            $appendix = '';
            $spacePos = strpos($content, ' ');
            if ($spacePos) {
                $appendix = substr($content, $spacePos);
                $content = substr($content, 0, $spacePos);
            }

            preg_match('/\$[^\s]+/', $appendix, $matches);
            $variable = $matches ? $matches[0] : '';

            $docBlockParams[] = [
                'index' => $classNameIndex,
                'type' => $content,
                'variable' => $variable,
                'appendix' => $appendix,
            ];
        }

        return $docBlockParams;
    }
}
