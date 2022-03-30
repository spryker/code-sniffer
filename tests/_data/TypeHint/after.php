<?php declare(strict_types = 1);

namespace Spryker;

class FixMe
{
    /**
     * @var string[][]
     */
    protected $securityRoutes = [];

    /**
     * @var array<string|int, array<mixed, array<string>|string|null>|string|null>|string|null
     */
    protected $arrays;

    /**
     * @return string[]|null
     */
    public function one(): ?array
    {
        return [];
    }

    /**
     * @param (string|int)[] $test
     *
     * @return string[]|int[]
     */
    public function second(array $test): array
    {
        return [];
    }

    /**
     * @param \ArrayObject|int[] $array
     *
     * @return \ArrayAccess|array<int> $array
     */
    public function third(array $array): array
    {
        return [];
    }

    /**
     * @param \Collection|int[] $array
     *
     * @return \Collection|array<int> $array
     */
    public function collection(array $array): array
    {
        return [];
    }

    /**
     * @param \Propel\Runtime\Collection\ObjectCollection|iterable<\Orm\Zed\Sales\Persistence\SpySalesShipment> $col
     *
     * @return \Propel\Runtime\Collection\Collection|\Propel\Runtime\Collection\ObjectCollection<\Propel\Runtime\ActiveRecord\ActiveRecordInterface>
     */
    protected function complex($col)
    {
        /** @var \Propel\Runtime\Collection\ObjectCollection<\Orm\Zed\SalesReturn\Persistence\SpySalesReturn> $salesReturnEntityCollection */
        $salesReturnEntityCollection = $this->runQuery();

        /** @var \ArrayObject<\Generated\Shared\Transfer\ShipmentGroupTransfer> $shipmentGroupCollection */
        $shipmentGroupCollection = $options[static::OPTION_SHIPMENT_GROUPS];

        return $salesReturnEntityCollection->getXyz();
    }

    /**
     * @param \Propel\Runtime\Collection|array<int|string>|string|int $x
     *
     * @return \Propel\Runtime\Collection|\ArrayObject<\Foo>|array<int|string>|string|int
     */
    protected function sortMultiple($x)
    {
        return $x;
    }

    /**
     * @return \Generator<array<\Generated\Shared\Transfer\ProductAbstractTransfer>>
     */
    public function mergeGenerics(): Generator
    {
        yield $this->x();
    }

    /**
     * @param \Propel\Runtime\Collection\ObjectCollection<\Orm\Zed\ProductImage\Persistence\SpyProductImageSet> $productImageSetEntities
     *
     * @return array<\Generated\Shared\Transfer\ProductImageSetTransfer>
     */
    protected function complexGeneric(ObjectCollection $productImageSetEntities): array
    {
        return [];
    }

    /**
     * @param \ArrayObject<string, mixed> $options
     * @return array<string>
     */
    public function merge($options): array
    {
        return [];
    }

    /**
     * @return array<string, array<string, string|null>|string|null>|string
     */
    public function complexUnion(): array
    {
        return [];
    }
}
