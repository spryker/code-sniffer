<?php
/**
 * (c) Spryker Systems GmbH copyright protected.
 */
namespace Spryker\Traits;

trait BasicsTrait
{

    /**
     * @param array $token
     * @param string|array $kind
     * @return bool
     */
    public function isGivenKind(array $token, $kind)
    {
        $kind = (array)$kind;

        if (in_array($token['code'], $kind, true)) {
            return true;
        }
        if (in_array($token['type'], $kind, true)) {
            return true;
        }

        return false;
    }

}
