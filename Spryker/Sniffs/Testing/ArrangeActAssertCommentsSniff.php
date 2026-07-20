<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Testing;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Test methods should be structured with // Arrange, // Act, // Assert section comments.
 */
class ArrangeActAssertCommentsSniff extends AbstractSprykerSniff
{
    /**
     * @var string
     */
    protected const CODE_MISSING_AAA_COMMENTS = 'MissingAaaComments';

    /**
     * @var string
     */
    protected const MESSAGE_MISSING_AAA_COMMENTS = 'Test method "%s()" has no // Arrange, // Act, // Assert section comments; structure the test with all three.';

    /**
     * @var string
     */
    protected const TEST_METHOD_PREFIX = 'test';

    /**
     * @var string
     */
    protected const AAA_COMMENT_PATTERN = '~^//\s*(Arrange|Act|Assert)\b~i';

    /**
     * @var int
     */
    protected const REQUIRED_SECTION_COUNT = 3;

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
        if (!$this->isTest($phpcsFile)) {
            return $phpcsFile->numTokens;
        }

        $methodName = $phpcsFile->getDeclarationName($stackPtr);
        if ($methodName === null || strncmp($methodName, static::TEST_METHOD_PREFIX, strlen(static::TEST_METHOD_PREFIX)) !== 0) {
            return null;
        }

        $tokens = $phpcsFile->getTokens();
        if (!isset($tokens[$stackPtr]['scope_opener'], $tokens[$stackPtr]['scope_closer'])) {
            return null;
        }

        $foundSections = [];
        for ($i = $tokens[$stackPtr]['scope_opener'] + 1; $i < $tokens[$stackPtr]['scope_closer']; $i++) {
            if ($tokens[$i]['code'] !== T_COMMENT) {
                continue;
            }

            $matches = [];
            if (preg_match(static::AAA_COMMENT_PATTERN, trim($tokens[$i]['content']), $matches) !== 1) {
                continue;
            }

            $foundSections[strtolower($matches[1])] = true;
            if (count($foundSections) === static::REQUIRED_SECTION_COUNT) {
                return null;
            }
        }

        if ($foundSections !== []) {
            return null;
        }

        $phpcsFile->addWarning(
            static::MESSAGE_MISSING_AAA_COMMENTS,
            $stackPtr,
            static::CODE_MISSING_AAA_COMMENTS,
            [$methodName],
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
        $shortName = (string)end($parts);

        return preg_match('/(Test|Cest)$/', $shortName) === 1;
    }
}
