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
     * @return \Collection|int[] $array
     */
    public function collection(array $array): array
    {
        return [];
    }

    /**
     * @param \Propel\Runtime\Collection\ObjectCollection|\Orm\Zed\Sales\Persistence\SpySalesShipment[] $col
     *
     * @return \Propel\Runtime\Collection\Collection|\Propel\Runtime\Collection\ObjectCollection|\Propel\Runtime\ActiveRecord\ActiveRecordInterface[]
     */
    protected function complex($col)
    {
        /** @var \Propel\Runtime\Collection\ObjectCollection|\Orm\Zed\SalesReturn\Persistence\SpySalesReturn[] $salesReturnEntityCollection */
        $salesReturnEntityCollection = $this->runQuery();

        /** @var \ArrayObject<\Generated\Shared\Transfer\ShipmentGroupTransfer> $shipmentGroupCollection */
        $shipmentGroupCollection = $options[static::OPTION_SHIPMENT_GROUPS];

        return $salesReturnEntityCollection->getXyz();
    }

    /**
     * @return \Generator<array<\Generated\Shared\Transfer\ProductAbstractTransfer>>
     */
    public function getRelatedProducts(): Generator
    {
        yield $this->x();
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
