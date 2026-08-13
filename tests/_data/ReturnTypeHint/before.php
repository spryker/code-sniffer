<?php

namespace App;

class Foo
{
    /**
     * @return \ArrayObject<int, \Generated\Shared\Transfer\ServiceTransfer>|mixed
     */
    public function keepGeneric(): mixed
    {
        return null;
    }

    /**
     * @return array{sku: string, quantity: int}|mixed
     */
    public function keepArrayShape(): mixed
    {
        return null;
    }

    /**
     * @return string
     */
    public function removeRestatedTypeHint(): string
    {
        return '';
    }

    /**
     * @return string|mixed
     */
    public function removeMixedUnionWithoutDetail(): mixed
    {
        return null;
    }
}
