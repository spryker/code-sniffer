<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Helpers\UseStatementHelper;

/**
 * Checks that attributes resolve unambiguously: either written as `\FQCN`, or referenced by the
 * short name of a `use` import. A short name without a matching import silently resolves against
 * the current namespace, which is what this reports.
 */
class AttributesSniff implements Sniff
{
    protected const string CODE_EXPECTED_FQCN = 'ExpectedFQCN';

    protected const string MESSAGE_EXPECTED_FQCN = 'FQCN or matching `use` import expected for attribute';

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_ATTRIBUTE,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpCsFile, $stackPointer): void
    {
        foreach ($this->getAttributeNameIndexes($phpCsFile, $stackPointer) as $nameIndex) {
            if ($this->isResolvable($phpCsFile, $nameIndex)) {
                continue;
            }

            $phpCsFile->addError(static::MESSAGE_EXPECTED_FQCN, $nameIndex, static::CODE_EXPECTED_FQCN);
        }
    }

    /**
     * One `#[...]` can group several attributes, e.g. `#[Foo, \Bar]`, so every name in the group
     * has to be collected — not just the first one after the opener.
     *
     * @return array<int>
     */
    protected function getAttributeNameIndexes(File $phpCsFile, int $stackPointer): array
    {
        $tokens = $phpCsFile->getTokens();
        if (!isset($tokens[$stackPointer]['attribute_closer'])) {
            return [];
        }

        $closerIndex = $tokens[$stackPointer]['attribute_closer'];
        $nameIndexes = [];
        $index = $stackPointer;

        while ($index !== null) {
            $nameIndex = $phpCsFile->findNext(Tokens::$emptyTokens, $index + 1, $closerIndex, true);
            if ($nameIndex === false) {
                break;
            }

            $nameIndexes[] = $nameIndex;
            $index = $this->findNextAttributeSeparator($phpCsFile, $nameIndex, $closerIndex);
        }

        return $nameIndexes;
    }

    /**
     * The comma that ends one attribute of a group. Argument lists are skipped whole, so a comma
     * between two arguments does not read as the start of the next attribute.
     *
     * @return int|null
     */
    protected function findNextAttributeSeparator(File $phpCsFile, int $nameIndex, int $closerIndex): ?int
    {
        $tokens = $phpCsFile->getTokens();

        for ($index = $nameIndex; $index < $closerIndex; $index++) {
            if ($tokens[$index]['code'] === T_OPEN_PARENTHESIS) {
                $index = $tokens[$index]['parenthesis_closer'];

                continue;
            }

            if ($tokens[$index]['code'] === T_COMMA) {
                return $index;
            }
        }

        return null;
    }

    protected function isResolvable(File $phpCsFile, int $nameIndex): bool
    {
        $tokens = $phpCsFile->getTokens();

        if ($tokens[$nameIndex]['code'] === T_NS_SEPARATOR) {
            return true;
        }

        if ($tokens[$nameIndex]['code'] !== T_STRING) {
            return true;
        }

        return $this->isImported($phpCsFile, $nameIndex, $tokens[$nameIndex]['content']);
    }

    /**
     * A partially qualified attribute such as `#[Attr\Covers]` resolves through the import of its
     * first segment, so only that segment is looked up.
     *
     * @return bool
     */
    protected function isImported(File $phpCsFile, int $nameIndex, string $name): bool
    {
        foreach (UseStatementHelper::getUseStatementsForPointer($phpCsFile, $nameIndex) as $useStatement) {
            if (!$useStatement->isClass()) {
                continue;
            }

            if (strtolower($useStatement->getNameAsReferencedInFile()) === strtolower($name)) {
                return true;
            }
        }

        return false;
    }
}
