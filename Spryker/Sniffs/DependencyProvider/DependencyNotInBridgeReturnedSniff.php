<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\DependencyProvider;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

class DependencyNotInBridgeReturnedSniff extends AbstractSprykerSniff
{
    protected const DEPENDENCY_TYPES = [
        'facade',
        'client',
        'service',
        'resource'
    ];

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

        $this->assertDependencyNotInBridgeReturned($phpCsFile, $stackPointer);
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
     * @return void
     */
    protected function assertDependencyNotInBridgeReturned(File $phpCsFile, int $stackPointer): void
    {
        $tokens = $phpCsFile->getTokens();
        $returnPointer = (int)$phpCsFile->findNext(T_RETURN, $stackPointer);
        $endOfLinePointer = $phpCsFile->findEndOfStatement($returnPointer);

        $statementTokens = array_slice($tokens, $returnPointer, $endOfLinePointer - $returnPointer);
        $statement = $this->parseTokensContent($statementTokens);
        $regExp = sprintf(
            '/return \$container->getLocator\(\)->(.*?)\(\)->(%s)\(\)/',
            implode('|', static::DEPENDENCY_TYPES)
        );

        if (!preg_match($regExp, $statement)) {
            return;
        }

        $errorMessage = $this->getErrorMessage($this->getClassName($phpCsFile), $statement);
        $phpCsFile->addError($errorMessage, $stackPointer, 'BridgeMissing');
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

    /**
     * @param string $className
     * @param string $statement
     *
     * @return string
     */
    protected function getErrorMessage(string $className, string $statement): string
    {
        $dependencyType = $this->getDependencyType($statement);

        return  sprintf(
            '%s returns a %2$s directly. Fix this by adding a bridge and injecting the given %2$s.',
            $className,
            $dependencyType
        );
    }

    /**
     * @param string $statement
     *
     * @return string
     */
    protected function getDependencyType(string $statement): string
    {
        $regExp = sprintf('/(?<=->)(%s)(?=\(\))/', implode('|', static::DEPENDENCY_TYPES));
        preg_match($regExp, $statement, $dependencyType);

        return $dependencyType[0] ?? 'dependency';
    }
}
