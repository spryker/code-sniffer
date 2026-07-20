<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Persistence;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Raw SQL in the Persistence layer must stay portable across MySQL, MariaDB and PostgreSQL.
 * Database-specific functions and backtick-quoted identifiers are not allowed.
 */
class DbSpecificSqlSniff extends AbstractSprykerSniff
{
    /**
     * @var string
     */
    protected const CODE_DB_SPECIFIC_SQL = 'DbSpecificSql';

    /**
     * @var string
     */
    protected const MESSAGE_DB_SPECIFIC_SQL = 'String contains database-specific SQL (%s); this is not portable across MySQL/MariaDB/PostgreSQL.';

    /**
     * @var array<string>
     */
    protected const FORBIDDEN_FUNCTIONS = ['GROUP_CONCAT', 'IFNULL('];

    /**
     * @var string
     */
    protected const BACKTICK_IDENTIFIER_PATTERN = '/`[^`\s][^`]*`/';

    /**
     * @var string
     */
    protected const LAYER_PERSISTENCE = 'Persistence';

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_CONSTANT_ENCAPSED_STRING, T_DOUBLE_QUOTED_STRING, T_HEREDOC, T_NOWDOC];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        if (!$this->isPersistence($phpcsFile)) {
            return $phpcsFile->numTokens;
        }

        $content = $phpcsFile->getTokens()[$stackPtr]['content'];

        foreach (static::FORBIDDEN_FUNCTIONS as $forbiddenFunction) {
            if (stripos($content, $forbiddenFunction) === false) {
                continue;
            }

            $phpcsFile->addError(
                static::MESSAGE_DB_SPECIFIC_SQL,
                $stackPtr,
                static::CODE_DB_SPECIFIC_SQL,
                [rtrim($forbiddenFunction, '(')],
            );

            return null;
        }

        if (preg_match(static::BACKTICK_IDENTIFIER_PATTERN, $content) !== 1) {
            return null;
        }

        $phpcsFile->addError(
            static::MESSAGE_DB_SPECIFIC_SQL,
            $stackPtr,
            static::CODE_DB_SPECIFIC_SQL,
            ['backtick-quoted identifier'],
        );

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     *
     * @return bool
     */
    protected function isPersistence(File $phpcsFile): bool
    {
        $className = $this->getClassName($phpcsFile);
        if ($className === '') {
            return false;
        }

        $parts = explode('\\', $className);
        $layer = $parts[3] ?? '';

        return $layer === static::LAYER_PERSISTENCE;
    }
}
