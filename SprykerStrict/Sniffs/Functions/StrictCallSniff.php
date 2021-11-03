<?php declare(strict_types = 1);

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerStrict\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Helpers\ClassHelper;
use SlevomatCodingStandard\Helpers\EmptyFileException;
use SlevomatCodingStandard\Helpers\NamespaceHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use SlevomatCodingStandard\Sniffs\Functions\StrictCallSniff as SlevomatStrictCallSniff;

/**
 * Makes sure, that certain methods with type unsafe params are handled in strict mode.
 * in_array(), array_keys(), array_search(), base64_decode() etc
 */
class StrictCallSniff extends SlevomatStrictCallSniff
{
    /**
     * If not defined, will only be enabled for core level.
     *
     * @var bool|null
     */
    public $enable;

    /**
     * @var string
     */
    protected const NAMESPACE_SPRYKER = 'Spryker';

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stringPointer): void
    {
        $this->enable = $this->enable ?? $this->isCore($phpcsFile);

        if (!$this->enable) {
            return;
        }

        parent::process($phpcsFile, $stringPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isCore(File $phpCsFile): bool
    {
        $namespace = $this->getNamespace($phpCsFile);

        return strpos($namespace, static::NAMESPACE_SPRYKER) === 0;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getNamespace(File $phpCsFile): string
    {
        $className = $this->getClassNameWithNamespace($phpCsFile);
        if (!$className) {
            return '';
        }

        $classNameParts = explode('\\', ltrim($className, '\\'));

        return $classNameParts[0];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string|null
     */
    protected function getClassNameWithNamespace(File $phpCsFile): ?string
    {
        try {
            $lastToken = TokenHelper::getLastTokenPointer($phpCsFile);
        } catch (EmptyFileException $e) {
            return null;
        }

        if (!NamespaceHelper::findCurrentNamespaceName($phpCsFile, $lastToken)) {
            return null;
        }

        $prevIndex = $phpCsFile->findPrevious(TokenHelper::$typeKeywordTokenCodes, $lastToken);
        if (!$prevIndex) {
            return null;
        }

        return ClassHelper::getFullyQualifiedName(
            $phpCsFile,
            $prevIndex,
        );
    }
}
