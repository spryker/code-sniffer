<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Traits\UseStatementsTrait;

/**
 * Makes sure the Pyz (project) namespace does not leak into the Spryker core one.
 */
class SprykerNoPyzSniff extends AbstractSprykerSniff
{
    use UseStatementsTrait;

    protected const NAMESPACE_PROJECT = 'Pyz';

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_CLASS, T_INTERFACE, T_TRAIT];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
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
    protected function extractNamespace(string $fullClassName): string
    {
        $namespaces = explode('\\', $fullClassName, 2);

        return $namespaces[0];
    }
}
