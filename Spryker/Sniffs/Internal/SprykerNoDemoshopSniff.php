<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Internal;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Makes sure the project specific non-split code does not leak into the demoshop repository.
 */
class SprykerNoDemoshopSniff extends AbstractSprykerSniff
{
    /**
     * @var bool|null
     */
    protected static $isDemoshop = null;

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_NAMESPACE];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        if (!$this->isDemoshopCode($phpcsFile)) {
            return;
        }

        $file = $phpcsFile->getFilename();
        $content = file_get_contents($file);

        if (!preg_match('/\* @project\b/', $content)) {
            return;
        }

        $phpcsFile->addError('No project only code should be merged into Spryker demoshop.', $stackPtr, 'InvalidContent');
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return bool
     */
    protected function isDemoshopCode(File $phpCsFile): bool
    {
        if (static::$isDemoshop !== null) {
            return static::$isDemoshop;
        }

        $positionSprykerCore = strpos($phpCsFile->getFilename(), '/src/');
        if (!$positionSprykerCore) {
            return false;
        }

        $file = substr($phpCsFile->getFilename(), 0, $positionSprykerCore) . '/composer.json';
        if (!is_file($file)) {
            static::$isDemoshop = false;

            return static::$isDemoshop;
        }

        $content = file_get_contents($file);
        static::$isDemoshop = (bool)preg_match('#"name":\s*"(spryker/demoshop|spryker-shop/suite|spryker-shop/suite-b2c|spryker-shop/suite-b2b)"#', $content, $matches);

        return static::$isDemoshop;
    }
}
