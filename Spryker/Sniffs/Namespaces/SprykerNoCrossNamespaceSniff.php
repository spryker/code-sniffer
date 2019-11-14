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
 * Makes sure the Yves namespace does not leak into the Spryker Zed one etc.
 */
class SprykerNoCrossNamespaceSniff extends AbstractSprykerSniff
{
    use UseStatementsTrait;

    protected const NAMESPACE_YVES = 'Yves';
    protected const NAMESPACE_ZED = 'Zed';

    protected const INVALID_PAIRS = [
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
        $className = $this->getClassName($phpcsFile);
        $namespaces = [];
        foreach (static::INVALID_PAIRS as $pair) {
            $namespaces[] = $pair['from'];
        }
        $namespaces = implode('|', $namespaces);

        if (!preg_match('#^\w+\\\\(' . $namespaces . ')\\\\#', $className, $matches)) {
            return;
        }

        if (strpos($className, 'PyzTest') === 0) {
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
    protected function checkUseStatement(File $phpcsFile, array $useStatement, string $applicationLayer): void
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
