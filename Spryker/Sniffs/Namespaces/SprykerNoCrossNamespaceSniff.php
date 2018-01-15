<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Traits\UseStatementsTrait;

/**
 * Makes sure the Yves namespace does not leak into the Spryker Zed one etc.
 */
class SprykerNoCrossNamespaceSniff extends AbstractSprykerSniff
{
    use UseStatementsTrait;

    const NAMESPACE_YVES = 'Yves';
    const NAMESPACE_ZED = 'Zed';

    const INVALID_PAIRS = [
        [
            'from' => self::NAMESPACE_YVES,
            'to' => self::NAMESPACE_ZED,
        ],
        [
            'from' => self::NAMESPACE_ZED,
            'to' => self::NAMESPACE_YVES,
        ],
    ];

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
        $namespaces = [];
        foreach (static::INVALID_PAIRS as $pair) {
            $namespaces[] = $pair['from'];
        }
        $namespaces = implode('|', $namespaces);

        if (!preg_match('#^\w+\\\\(' . $namespaces . ')\\\\#', $className, $matches)) {
            return;
        }

        $useStatements = $this->getUseStatements($phpcsFile);
        foreach ($useStatements as $useStatement) {
            $this->checkUseStatement($phpcsFile, $useStatement, $matches[1]);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param array $useStatement
     * @param string $applicationLayer Zed, Yves, ...
     *
     * @return void
     */
    protected function checkUseStatement(File $phpcsFile, array $useStatement, $applicationLayer)
    {
        $className = $useStatement['fullName'];

        $pairsToCheck = static::INVALID_PAIRS;
        foreach ($pairsToCheck as $pair) {
            if ($pair['from'] !== $applicationLayer) {
                continue;
            }

            if (!preg_match('#^\w+\\\\' . $pair['to'] . '\\\\#', $className, $matches)) {
                continue;
            }

            $phpcsFile->addError(
                sprintf(
                    'No %s namespace allowed in %s files.',
                    $pair['to'],
                    $pair['from']
                ),
                $useStatement['start'],
                'InvalidCrossNamespace'
            );
        }
    }
}
