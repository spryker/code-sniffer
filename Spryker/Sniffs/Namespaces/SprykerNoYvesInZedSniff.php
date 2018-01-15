<?php
/**
 * (c) Spryker Systems GmbH copyright protected.
 */

namespace Spryker\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Traits\UseStatementsTrait;

/**
 * Makes sure the Yves namespace does not leak into the Spryker Zed one.
 */
class SprykerNoYvesInZedSniff extends AbstractSprykerSniff
{
    use UseStatementsTrait;

    const NAMESPACE_YVES = 'Yves';
    const NAMESPACE_ZED = 'Zed';

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
    public function process(File $phpcsFile, $stackPtr)
    {
        $className = $this->getClassName($phpcsFile);
        if (!preg_match('#^\w+\\\\' . static::NAMESPACE_ZED . '\\\\#', $className, $matches)) {
            return;
        }

        $useStatements = $this->getUseStatements($phpcsFile);
        foreach ($useStatements as $useStatement) {
            $className = $useStatement['fullName'];
            if (!preg_match('#^\w+\\\\' . static::NAMESPACE_YVES . '\\\\#', $className, $matches)) {
                continue;
            }

            $phpcsFile->addError(
                sprintf(
                    'No %s namespace allowed in %s files.',
                    static::NAMESPACE_YVES,
                    static::NAMESPACE_ZED
                ),
                $useStatement['start'],
                'InvalidYvesInZed'
            );
        }
    }
}
