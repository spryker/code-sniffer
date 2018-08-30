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
 * Makes sure doc block param/return types have the right order and don't duplicate.
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
     * @var array
     */
    protected $sortMap = [
        'void',
        'null',
        'false',
    ];

    /**
     * @inheritDoc
     */
    public function register()
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
        $tokens = $phpCsFile->getTokens();

        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);

        if (!$docBlockEndIndex) {
            return;
        }

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        if ($this->hasInheritDoc($phpCsFile, $docBlockStartIndex, $docBlockEndIndex)) {
            return;
        }

        $docBlockParams = [];
        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if (!in_array($tokens[$i]['content'], ['@param', '@return'])) {
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
            if (strpos($docBlockParam['type'], '$') !== false) {
                continue;
            }

            $pieces = explode('|', $docBlockParam['type']);
            if (count($pieces) === 1) {
                continue;
            }

            $unique = array_unique($pieces);
            if (count($pieces) !== count($unique)) {
                $phpCsFile->addError('Duplicate type in `' . $docBlockParam['type'] . '`', $docBlockParam['index'], 'Duplicate');
                continue;
            }
            $expectedOrder = $this->getExpectedOrder($pieces);
            if ($expectedOrder === $pieces) {
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
     * @param array $pieces
     *
     * @return array
     */
    protected function getExpectedOrder(array $pieces): array
    {
        global $sortOrder;

        $sortOrder = array_reverse($this->sortMap);
        usort($pieces, [$this, "compare"]);

        return $pieces;
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

        $ai = array_search($a, $sortOrder);
        $bi = array_search($b, $sortOrder);
        if ($ai === false && $bi === false) {
            return -1;
        }
        if ($ai !== false && $bi === false) {
            return 1;
        }
        if ($bi !== false && $ai === false) {
            return -1;
        }
        if ($ai !== false && $bi !== false) {
            return $ai - $bi;
        }

        return $a < $b ? -1 : 1;
    }
}
