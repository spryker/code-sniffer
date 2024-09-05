<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Internal;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Do not use functions that are not available for lowest version supported.
 * By default, only applies to core if no PHP version is passed in.
 *
 * Can be removed/disabled with use of symfony polyfills. Thus the $phpVersion config.
 * If you use PHP 8+ on project level and do not need this additional check:
 *
 * <rule ref="Spryker.Internal.SprykerDisallowFunctions">
 *     <properties>
 *         <property name="phpVersion" value="off"/> // Instead of 8.0/8.1/...
 *     </properties>
 * </rule>
 */
class SprykerDisallowFunctionsSniff extends AbstractSprykerSniff
{
    /**
     * @var string
     */
    protected const PHP_MIN = '8.1';

    /**
     * This property can be filled with the current PHP version in use.
     * E.g. 8.0 activates 8.1+ checks. Set to string `off` to deactivate whole sniff.
     * If not filled, it will default to core use only.
     *
     * @var string|null
     */
    public $phpVersion;

    /**
     * @var array<string, array<string>>
     */
    protected static $methods = [
        // https://github.com/symfony/polyfill-php81
        '8.1' => [
            'array_is_list',
        ],
    ];

    /**
     * @var bool|null
     */
    protected static $enabled;

    /**
     * @var array<string>
     */
    protected static $disallowed = [];

    /**
     * @var array<int>
     */
    protected static $wrongTokens = [T_FUNCTION, T_OBJECT_OPERATOR, T_NEW, T_DOUBLE_COLON];

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_STRING];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        if (!$this->isEnabled($phpcsFile)) {
            return;
        }

        $this->checkForbiddenFunctions($phpcsFile, $stackPtr);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkForbiddenFunctions(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $tokenContent = $tokens[$stackPtr]['content'];
        $key = strtolower($tokenContent);
        if (!in_array($key, static::$disallowed, true)) {
            return;
        }

        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if (!$previous || in_array($tokens[$previous]['code'], static::$wrongTokens, true)) {
            return;
        }

        $openingBrace = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if (!$openingBrace || $tokens[$openingBrace]['type'] !== 'T_OPEN_PARENTHESIS') {
            return;
        }

        $error = $tokenContent . '() usage found. This function cannot be used in code yet.';
        $phpcsFile->addError($error, $stackPtr, 'Invalid');
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     *
     * @return bool
     */
    protected function isEnabled(File $phpcsFile): bool
    {
        $version = $this->phpVersion;
        if ($version === 'off') {
            return false;
        }

        // Use static cache for method list to optimize time
        if (static::$enabled !== null) {
            return static::$enabled;
        }

        if ($version === null && $this->isCore($phpcsFile)) {
            $version = static::PHP_MIN;
        }

        if (!$version) {
            return false;
        }

        foreach (static::$methods as $php => $phpMethods) {
            if (version_compare($php, $version) > 0) {
                static::$disallowed = array_merge(static::$disallowed, $phpMethods);
            }
        }

        static::$enabled = true;

        return true;
    }
}
