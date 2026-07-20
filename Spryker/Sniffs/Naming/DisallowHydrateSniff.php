<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Naming;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * The hydrate/Hydrator vocabulary is forbidden in Spryker code;
 * use "expand" method / "Expander" class naming instead.
 */
class DisallowHydrateSniff extends AbstractSprykerSniff
{
    /**
     * @var string
     */
    protected const CODE_HYDRATE_FORBIDDEN = 'HydrateForbidden';

    /**
     * @var string
     */
    protected const MESSAGE_HYDRATE_FORBIDDEN = '"%s" uses the forbidden hydrate/Hydrator vocabulary; use expand*/Expander naming instead.';

    /**
     * @var string
     */
    protected const FORBIDDEN_NAME_PATTERN = '/hydrate|hydrator/i';

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_CLASS, T_INTERFACE, T_TRAIT, T_FUNCTION];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        if ($this->isTest($phpcsFile)) {
            return $phpcsFile->numTokens;
        }

        $declarationName = $phpcsFile->getDeclarationName($stackPtr);
        if ($declarationName === null) {
            return null;
        }

        if (preg_match(static::FORBIDDEN_NAME_PATTERN, $declarationName) !== 1) {
            return null;
        }

        $phpcsFile->addError(
            static::MESSAGE_HYDRATE_FORBIDDEN,
            $stackPtr,
            static::CODE_HYDRATE_FORBIDDEN,
            [$declarationName],
        );

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     *
     * @return bool
     */
    protected function isTest(File $phpcsFile): bool
    {
        $className = $this->getClassName($phpcsFile);
        if ($className === '') {
            return false;
        }

        $parts = explode('\\', $className);
        $root = $parts[0];
        $shortName = (string)end($parts);

        return substr($root, -4) === 'Test' || preg_match('/(Test|Cest)$/', $shortName) === 1;
    }
}
