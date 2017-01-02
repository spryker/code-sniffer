<?php
/**
 * (c) Spryker Systems GmbH copyright protected.
 */

namespace Spryker\Traits;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Tokens;

trait UseStatementsTrait
{

    /**
     * @return array
     */
    protected function getUseStatements(PHP_CodeSniffer_File $phpcsFile)
    {
        $tokens = $phpcsFile->getTokens();

        $statements = [];
        foreach ($tokens as $index => $token) {
            if ($token['code'] !== T_USE || $token['level'] > 0) {
                continue;
            }

            $useStatementStartIndex = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, $index + 1, null, true);

            // Ignore function () use ($foo) {}
            if ($tokens[$useStatementStartIndex]['content'] === '(') {
                continue;
            }

            $semicolonIndex = $phpcsFile->findNext(T_SEMICOLON, $useStatementStartIndex + 1);
            $useStatementEndIndex = $phpcsFile->findPrevious(PHP_CodeSniffer_Tokens::$emptyTokens, $semicolonIndex - 1, null, true);

            $statement = '';
            for ($i = $useStatementStartIndex; $i <= $useStatementEndIndex; $i++) {
                $statement .= $tokens[$i]['content'];
            }

            // Another sniff takes care of that, we just ignore then.
            if ($this->isMultipleUseStatement($statement)) {
                continue;
            }

            $statementParts = preg_split('/\s+as\s+/i', $statement);

            if (count($statementParts) === 1) {
                $fullName = $statement;
                $statementParts = explode('\\', $fullName);
                $shortName = end($statementParts);
                $alias = null;
            } else {
                $fullName = $statementParts[0];
                $alias = $statementParts[1];
                $statementParts = explode('\\', $fullName);
                $shortName = end($statementParts);
            }

            $shortName = trim($shortName);
            $fullName = trim($fullName);
            $key = $alias ?: $shortName;

            $statements[$key] = [
                'alias' => $alias,
                'end' => $semicolonIndex,
                'fullName' => ltrim($fullName, '\\'),
                'shortName' => $shortName,
                'start' => $index,
            ];
        }

        return $statements;
    }

    /**
     * @param string $statementContent
     *
     * @return bool
     */
    protected function isMultipleUseStatement($statementContent)
    {
        if (strpos($statementContent, ',') !== false) {
            return true;
        }

        return false;
    }

}
