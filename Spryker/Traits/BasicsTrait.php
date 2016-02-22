<?php
/**
 * (c) Spryker Systems GmbH copyright protected.
 */
namespace Spryker\Traits;

trait BasicsTrait
{

    /**
     * @param string|array $search
     * @param array $token
     * @return bool
     */
    public function isGivenKind($search, array $token)
    {
        $kind = (array)$search;

        if (in_array($token['code'], $kind, true)) {
            return true;
        }
        if (in_array($token['type'], $kind, true)) {
            return true;
        }

        return false;
    }

}
