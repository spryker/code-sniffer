<?php declare(strict_types = 1);

namespace Spryker;

class FixMe
{
    /**
     * @return array<string>|null
     */
    public function one(): ?array
    {
        return [];
    }

    /**
     * @param array<string|int> $test
     *
     * @return array<string>|array<int>
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
}
