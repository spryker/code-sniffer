<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\DependencyProvider;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Spryker Locator should not return Facades directly, use a bridge instead.
 *
 * Note: This sniff will only run on Spryker Core files.
 */
class FacadeNotInBridgeReturnedSniff extends AbstractSprykerSniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_CLOSURE,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        if (!$this->isProvider($phpCsFile) || !$this->isCoreProvider($phpCsFile)) {
            return;
        }

        if ($this->isFacadeNotInBridgeReturned($phpCsFile, $stackPointer)) {
            $phpCsFile->addError(
                $this->getClassName($phpCsFile) . ' returns a facade directly. Fix this by adding a bridge and injecting the given facade.',
                $stackPointer,
                'BridgeMissing'
            );
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isProvider(File $phpCsFile): bool
    {
        $className = $this->getClassName($phpCsFile);
        $moduleName = $this->getModule($phpCsFile);

        $providerName = $moduleName . 'DependencyProvider';
        $stringLength = strlen($providerName);
        $relevantClassNamePart = substr($className, -$stringLength);

        return ($relevantClassNamePart === $providerName);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isCoreProvider(File $phpCsFile): bool
    {
        $namespace = $this->getNamespace($phpCsFile);

        return ($namespace === static::NAMESPACE_SPRYKER);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isFacadeNotInBridgeReturned(File $phpCsFile, int $stackPointer): bool
    {
        $tokens = $phpCsFile->getTokens();
        $returnPointer = (int)$phpCsFile->findNext(T_RETURN, $stackPointer);
        $endOfLinePointer = $phpCsFile->findEndOfStatement($returnPointer);

        $statementTokens = array_slice($tokens, $returnPointer, $endOfLinePointer - $returnPointer);
        $statement = $this->parseTokensContent($statementTokens);

        if (preg_match('/return \$container->getLocator\(\)->(.*?)\(\)->facade\(\)/', $statement)) {
            return true;
        }

        return false;
    }

    /**
     * @param array $tokens
     *
     * @return string
     */
    protected function parseTokensContent(array $tokens): string
    {
        $statement = '';
        foreach ($tokens as $token) {
            $statement .= $token['content'];
        }

        return $statement;
    }
}
