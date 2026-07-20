<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Table;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Zed Table constants: COL_ and BUTTON_ constants must be a typed constant of type string
 * declared protected, and URL path constants must carry a @uses annotation linking the
 * referenced controller action.
 */
class TableConstantsSniff extends AbstractSprykerSniff
{
    /**
     * @var string
     */
    protected const CODE_COL_CONST_VISIBILITY = 'ColConstVisibility';

    /**
     * @var string
     */
    protected const CODE_COL_CONST_TYPE = 'ColConstType';

    /**
     * @var string
     */
    protected const CODE_MISSING_USES_ANNOTATION = 'MissingUsesAnnotation';

    /**
     * @var string
     */
    protected const MESSAGE_COL_CONST_VISIBILITY = 'Table constant "%s" must be declared protected, %s given.';

    /**
     * @var string
     */
    protected const MESSAGE_COL_CONST_TYPE = 'Table constant "%s" must be a typed constant of type string ("protected const string %s = ...").';

    /**
     * @var string
     */
    protected const MESSAGE_MISSING_USES_ANNOTATION = 'URL path constant "%s" must have a @uses tag in its docblock linking the referenced controller action.';

    /**
     * @var string
     */
    protected const TABLE_CONSTANT_PREFIX_PATTERN = '/^(COL_|BUTTON_)/';

    /**
     * @var string
     */
    protected const EXPECTED_TYPE = 'string';

    /**
     * @var string
     */
    protected const USES_TAG = '@uses';

    /**
     * @var string
     */
    protected const TABLE_SUFFIX = 'Table';

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_CONST];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        if (!$this->isTable($phpcsFile)) {
            return $phpcsFile->numTokens;
        }

        $tokens = $phpcsFile->getTokens();
        $statementEnd = $phpcsFile->findEndOfStatement($stackPtr);
        $equalPtr = $phpcsFile->findNext(T_EQUAL, $stackPtr + 1, $statementEnd);
        if ($equalPtr === false) {
            return null;
        }

        $namePtr = $phpcsFile->findPrevious(T_STRING, $equalPtr - 1, $stackPtr);
        if ($namePtr === false) {
            return null;
        }

        $constantName = $tokens[$namePtr]['content'];

        if (preg_match(static::TABLE_CONSTANT_PREFIX_PATTERN, $constantName) === 1) {
            $this->assertProtectedVisibility($phpcsFile, $stackPtr, $constantName);
            $this->assertStringType($phpcsFile, $stackPtr, $namePtr, $constantName);
        }

        $this->assertUsesAnnotationOnUrlConstant($phpcsFile, $stackPtr, $equalPtr, $constantName);

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     *
     * @return bool
     */
    protected function isTable(File $phpcsFile): bool
    {
        $className = $this->getClassName($phpcsFile);
        if ($className === '') {
            return false;
        }

        $parts = explode('\\', $className);
        $shortName = (string)end($parts);

        return substr($shortName, -strlen(static::TABLE_SUFFIX)) === static::TABLE_SUFFIX;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $constPtr
     * @param string $constantName
     *
     * @return void
     */
    protected function assertProtectedVisibility(File $phpcsFile, int $constPtr, string $constantName): void
    {
        $tokens = $phpcsFile->getTokens();
        $visibility = 'public (implicit)';

        for ($i = $constPtr - 1; $i >= 0; $i--) {
            $code = $tokens[$i]['code'];
            if ($code === T_WHITESPACE || $code === T_FINAL) {
                continue;
            }

            if ($code === T_PROTECTED) {
                return;
            }

            if ($code === T_PUBLIC || $code === T_PRIVATE) {
                $visibility = $tokens[$i]['content'];
            }

            break;
        }

        $phpcsFile->addError(
            static::MESSAGE_COL_CONST_VISIBILITY,
            $constPtr,
            static::CODE_COL_CONST_VISIBILITY,
            [$constantName, $visibility],
        );
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $constPtr
     * @param int $namePtr
     * @param string $constantName
     *
     * @return void
     */
    protected function assertStringType(File $phpcsFile, int $constPtr, int $namePtr, string $constantName): void
    {
        $tokens = $phpcsFile->getTokens();
        $type = '';

        for ($i = $constPtr + 1; $i < $namePtr; $i++) {
            if (isset(Tokens::$emptyTokens[$tokens[$i]['code']])) {
                continue;
            }

            $type .= $tokens[$i]['content'];
        }

        if (strtolower($type) === static::EXPECTED_TYPE) {
            return;
        }

        $phpcsFile->addError(
            static::MESSAGE_COL_CONST_TYPE,
            $constPtr,
            static::CODE_COL_CONST_TYPE,
            [$constantName, $constantName],
        );
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $constPtr
     * @param int $equalPtr
     * @param string $constantName
     *
     * @return void
     */
    protected function assertUsesAnnotationOnUrlConstant(File $phpcsFile, int $constPtr, int $equalPtr, string $constantName): void
    {
        $tokens = $phpcsFile->getTokens();

        $valuePtr = $phpcsFile->findNext(Tokens::$emptyTokens, $equalPtr + 1, null, true);
        if ($valuePtr === false || $tokens[$valuePtr]['code'] !== T_CONSTANT_ENCAPSED_STRING) {
            return;
        }

        $value = trim($tokens[$valuePtr]['content'], '\'"');
        if ($value === '' || $value[0] !== '/') {
            return;
        }

        if ($this->hasUsesTagInPrecedingDocBlock($phpcsFile, $constPtr)) {
            return;
        }

        $phpcsFile->addWarning(
            static::MESSAGE_MISSING_USES_ANNOTATION,
            $constPtr,
            static::CODE_MISSING_USES_ANNOTATION,
            [$constantName],
        );
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $constPtr
     *
     * @return bool
     */
    protected function hasUsesTagInPrecedingDocBlock(File $phpcsFile, int $constPtr): bool
    {
        $tokens = $phpcsFile->getTokens();

        $statementStart = $constPtr;
        for ($i = $constPtr - 1; $i >= 0; $i--) {
            $code = $tokens[$i]['code'];
            if ($code === T_WHITESPACE) {
                continue;
            }

            if ($code === T_FINAL || $code === T_PUBLIC || $code === T_PROTECTED || $code === T_PRIVATE) {
                $statementStart = $i;

                continue;
            }

            break;
        }

        $previousPtr = $phpcsFile->findPrevious(T_WHITESPACE, $statementStart - 1, null, true);
        if ($previousPtr === false || $tokens[$previousPtr]['code'] !== T_DOC_COMMENT_CLOSE_TAG) {
            return false;
        }

        $docBlockOpener = $tokens[$previousPtr]['comment_opener'];
        for ($i = $docBlockOpener; $i < $previousPtr; $i++) {
            if ($tokens[$i]['code'] !== T_DOC_COMMENT_TAG) {
                continue;
            }

            if ($tokens[$i]['content'] === static::USES_TAG) {
                return true;
            }
        }

        return false;
    }
}
