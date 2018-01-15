<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
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
     * @inheritdoc
     */
    public function register()
    {
        return [
            T_CLOSURE,
        ];
    }

    /**
     * @inheritdoc
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
    protected function isProvider(File $phpCsFile)
    {
        $className = $this->getClassName($phpCsFile);
        $bundleName = $this->getModule($phpCsFile);

        $providerName = $bundleName . 'DependencyProvider';
        $stringLength = strlen($providerName);
        $relevantClassNamePart = substr($className, -$stringLength);

        return ($relevantClassNamePart === $providerName);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isCoreProvider(File $phpCsFile)
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
    private function isFacadeNotInBridgeReturned(File $phpCsFile, $stackPointer)
    {
        $tokens = $phpCsFile->getTokens();
        $returnPointer = $phpCsFile->findNext(T_RETURN, $stackPointer);
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
    private function parseTokensContent(array $tokens)
    {
        $statement = '';
        foreach ($tokens as $token) {
            $statement .= $token['content'];
        }

        return $statement;
    }
}
