<?php
/**
 * (c) Spryker Systems GmbH copyright protected.
 */

namespace Spryker\Sniffs\Namespaces;

use PHP_CodeSniffer_File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Traits\UseStatementsTrait;

/**
 * Makes sure the Pyz (project) namespace does not leak into the Spryker core one.
 */
class SprykerNoPyzSniff extends AbstractSprykerSniff
{

    use UseStatementsTrait;

    const NAMESPACE_PROJECT = 'Pyz';

    /**
     * @inheritdoc
     */
    public function register()
    {
        return [T_CLASS, T_INTERFACE, T_TRAIT];
    }

    /**
     * @inheritdoc
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        if (!$this->isSprykerNamespace($phpcsFile)) {
            return;
        }

        $useStatements = $this->getUseStatements($phpcsFile);
        foreach ($useStatements as $useStatement) {
            $namespace = $this->extractNamespace($useStatement['fullName']);
            if ($namespace !== static::NAMESPACE_PROJECT) {
                continue;
            }

            $phpcsFile->addError(sprintf('No %s namespace allowed in core files.', static::NAMESPACE_PROJECT), $useStatement['start'], 'InvalidPyzInSpryker');
        }
    }

    /**
     * @param string $fullClassName
     *
     * @return string
     */
    protected function extractNamespace($fullClassName)
    {
        $namespaces = explode('\\', $fullClassName, 2);

        return $namespaces[0];
    }

    /**
     * @param \PHP_CodeSniffer_File $phpCsFile
     *
     * @return bool
     */
    protected function isSprykerNamespace(PHP_CodeSniffer_File $phpCsFile)
    {
        $namespace = $this->getNamespace($phpCsFile);

        return ($namespace === static::NAMESPACE_SPRYKER);
    }

}
