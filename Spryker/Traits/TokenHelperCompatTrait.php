<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Traits;

use SlevomatCodingStandard\Helpers\TokenHelper;

trait TokenHelperCompatTrait
{
    /**
     * Resolves a Slevomat `TokenHelper` token-code list by name, tolerating its move from a
     * public static property (Slevomat < 8.16) to a class constant (Slevomat >= 8.16). The
     * name is resolved dynamically so neither form appears as a static literal that would be
     * rejected by static analysis against whichever Slevomat version is installed.
     *
     * @return array<int|string>
     */
    protected function resolveTokenHelperCodes(string $constantName, string $propertyName): array
    {
        $qualifiedConstantName = TokenHelper::class . '::' . $constantName;
        $codes = defined($qualifiedConstantName)
            ? constant($qualifiedConstantName)
            : TokenHelper::${$propertyName};

        $result = [];
        if (is_array($codes)) {
            foreach ($codes as $code) {
                if (is_int($code) || is_string($code)) {
                    $result[] = $code;
                }
            }
        }

        return $result;
    }
}
