<?php

namespace App;

class Foo
{
    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ServiceTransfer>|mixed $serviceTransfers
     */
    public function keepGeneric(mixed $serviceTransfers): void
    {
    }

    /**
     * @param mixed|array<string>|null $uuids
     */
    public function keepArrayGeneric(mixed $uuids): void
    {
    }

    /**
     * @param array{sku: string, quantity: int}|mixed $item
     */
    public function keepArrayShape(mixed $item): void
    {
    }

    /**
     * @param \Generated\Shared\Transfer\StoreTransfer[]|mixed $storeTransfers
     */
    public function keepLegacyArraySyntax(mixed $storeTransfers): void
    {
    }

    /**
     */
    public function removeRestatedTypeHint(string $sku): void
    {
    }

    /**
     */
    public function removeMixedUnionWithoutDetail(mixed $value): void
    {
    }
}
