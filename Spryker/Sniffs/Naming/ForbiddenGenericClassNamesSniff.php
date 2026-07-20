<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Naming;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Zed Business layer classes must be named after their single responsibility,
 * not with catch-all suffixes such as Executor, Handler, Worker, Manager or Processor.
 */
class ForbiddenGenericClassNamesSniff extends AbstractSprykerSniff
{
    protected const string CODE_GENERIC_NAME = 'GenericName';

    protected const string MESSAGE_GENERIC_NAME = 'Zed Business class "%s" ends with the generic suffix "%s"; name the class after its single responsibility (e.g. Reader, Writer, Expander, Mapper, Validator) instead.';

    /**
     * @var array<string>
     */
    protected const array FORBIDDEN_SUFFIXES = ['Executor', 'Handler', 'Worker', 'Manager', 'Processor'];

    protected const string APPLICATION_ZED = 'Zed';

    protected const string LAYER_BUSINESS = 'Business';

    /**
     * Class short names exempted from this sniff (configurable via ruleset property).
     *
     * @var array<string>
     */
    public $allowedClasses = [];

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_CLASS];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        if (!$this->isZedBusiness($phpcsFile)) {
            return $phpcsFile->numTokens;
        }

        $className = $phpcsFile->getDeclarationName($stackPtr);
        if ($className === null || in_array($className, $this->allowedClasses, true)) {
            return null;
        }

        foreach (static::FORBIDDEN_SUFFIXES as $suffix) {
            if (substr($className, -strlen($suffix)) !== $suffix) {
                continue;
            }

            $phpcsFile->addWarning(
                static::MESSAGE_GENERIC_NAME,
                $stackPtr,
                static::CODE_GENERIC_NAME,
                [$className, $suffix],
            );

            return null;
        }

        return null;
    }

    protected function isZedBusiness(File $phpcsFile): bool
    {
        $className = $this->getClassName($phpcsFile);
        if ($className === '') {
            return false;
        }

        $parts = explode('\\', $className);
        $application = $parts[1] ?? '';
        $layer = $parts[3] ?? '';

        return $application === static::APPLICATION_ZED && $layer === static::LAYER_BUSINESS;
    }
}
