<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use Spryker\Traits\BridgeTrait;

/**
 * Checks if Spryker Bridge classes have a type-hinting-less constructor.
 */
class SprykerBridgeSniff implements Sniff
{
    use BridgeTrait;

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_CLASS,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        if (!$this->isSprykerBridge($phpCsFile, $stackPointer)) {
            return;
        }

        $this->checkBridge($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function checkBridge(File $phpCsFile, int $stackPointer): void
    {
        $index = $stackPointer;
        while ($index = $phpCsFile->findNext(T_FUNCTION, $index + 1)) {
            $methodName = $phpCsFile->getDeclarationName($index);
            if ($methodName !== '__construct') {
                continue;
            }

            $this->assertValidConstructor($phpCsFile, $index);
            $this->assertValidDocBlock($phpCsFile, $index);

            break;
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $index
     *
     * @return void
     */
    protected function assertValidConstructor(File $phpCsFile, int $index): void
    {
        $parameters = $phpCsFile->getMethodParameters($index);
        foreach ($parameters as $parameter) {
            if (empty($parameter['type_hint'])) {
                break;
            }

            $fix = $phpCsFile->addFixableError('Bridge constructors must not have typehints.', $parameter['token'], 'InvalidTypehint');
            if (!$fix) {
                break;
            }

            $variableIndex = $parameter['token'];
            $typehintIndex = $phpCsFile->findPrevious([T_STRING, T_NS_SEPARATOR], $variableIndex - 1);
            $firstIndex = $phpCsFile->findPrevious([T_STRING, T_NS_SEPARATOR], $typehintIndex - 1, null, true);

            $phpCsFile->fixer->beginChangeset();
            for ($i = $firstIndex + 1; $i < $variableIndex; $i++) {
                $phpCsFile->fixer->replaceToken($i, '');
            }
            $phpCsFile->fixer->endChangeset();
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $methodIndex
     *
     * @return void
     */
    protected function assertValidDocBlock(File $phpCsFile, int $methodIndex): void
    {
        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $methodIndex);
        if (!$docBlockEndIndex) {
            // Let another sniff take care of this
            return;
        }

        $tokens = $phpCsFile->getTokens();

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

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

            $spacePos = strpos($content, ' ');
            if ($spacePos) {
                $content = substr($content, 0, $spacePos);
            }
            if (substr($content, 0, 8) !== '\Spryker') {
                continue;
            }
            if (substr($content, -9) === 'Interface') {
                continue;
            }
            $pieces = explode('\\', $content);
            $lastPiece = array_pop($pieces);

            if (!$this->isRelevant($lastPiece)) {
                continue;
            }

            $error = 'Bridges should be annotated with interfaces only.';
            $phpCsFile->addError($error, $classNameIndex, 'WrongAnnotation');
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return int|null Stackpointer value of docblock end tag, or null if cannot be found
     */
    protected function findRelatedDocBlock(File $phpCsFile, int $stackPointer): ?int
    {
        $tokens = $phpCsFile->getTokens();

        $line = $tokens[$stackPointer]['line'];
        $beginningOfLine = $stackPointer;
        while (!empty($tokens[$beginningOfLine - 1]) && $tokens[$beginningOfLine - 1]['line'] === $line) {
            $beginningOfLine--;
        }

        if (!empty($tokens[$beginningOfLine - 2]) && $tokens[$beginningOfLine - 2]['type'] === 'T_DOC_COMMENT_CLOSE_TAG') {
            return $beginningOfLine - 2;
        }

        return null;
    }

    /**
     * @param string $content
     *
     * @return bool
     */
    protected function isRelevant(string $content): bool
    {
        $whitelist = [
            'Facade',
            'Service',
            'QueryContainer',
            'Client',
        ];

        foreach ($whitelist as $classType) {
            $length = strlen($classType);
            if (substr($content, -$length) === $classType) {
                return true;
            }
        }

        return false;
    }
}
