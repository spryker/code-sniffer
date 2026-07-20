<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Naming;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Plugin method names must use the "pre" and "post" prefixes, never "before" and "after".
 */
class PluginPrePostMethodsSniff extends AbstractSprykerSniff
{
    protected const string CODE_BEFORE_AFTER_FORBIDDEN = 'BeforeAfterForbidden';

    protected const string MESSAGE_BEFORE_AFTER_FORBIDDEN = 'Plugin method "%s()" must not start with "%s"; plugin methods use pre*/post* naming.';

    protected const string FORBIDDEN_PREFIX_PATTERN = '/^(before|after)(?=[A-Z0-9_]|$)/';

    protected const string SCOPE_PUBLIC = 'public';

    protected const string PLUGIN_SUFFIX = 'Plugin';

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_FUNCTION];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        if (!$this->isPlugin($phpcsFile)) {
            return $phpcsFile->numTokens;
        }

        $methodName = $phpcsFile->getDeclarationName($stackPtr);
        if ($methodName === null) {
            return null;
        }

        $methodProperties = $phpcsFile->getMethodProperties($stackPtr);
        if ($methodProperties['scope'] !== static::SCOPE_PUBLIC) {
            return null;
        }

        $matches = [];
        if (preg_match(static::FORBIDDEN_PREFIX_PATTERN, $methodName, $matches) !== 1) {
            return null;
        }

        $phpcsFile->addError(
            static::MESSAGE_BEFORE_AFTER_FORBIDDEN,
            $stackPtr,
            static::CODE_BEFORE_AFTER_FORBIDDEN,
            [$methodName, $matches[1]],
        );

        return null;
    }

    protected function isPlugin(File $phpcsFile): bool
    {
        $className = $this->getClassName($phpcsFile);
        if ($className === '') {
            return false;
        }

        $parts = explode('\\', $className);
        $shortName = (string)end($parts);

        if (substr($shortName, -strlen(static::PLUGIN_SUFFIX)) !== static::PLUGIN_SUFFIX) {
            return false;
        }

        return in_array(static::PLUGIN_SUFFIX, $parts, true);
    }
}
