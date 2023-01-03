<?php declare(strict_types = 1);

namespace Spryker;

class FixMe
{
    /**
     * @param array $array
     * @param string|null $string
     * @return void
     */
    public function dontTouchMe(array $array, $string = null): void
    {
    }

    /**
     * @param array $array
     * @param string $string
     * @return void
     */
    public function touchMe(array $array = null, $string = null): void
    {
    }

    /**
     * @param array<string, array<string, mixed>> $array
     * @return void
     */
    public function generics(array $array = null): void
    {
    }

    /**
     * @param iterable<\Generated\Shared\Transfer\SalesPaymentTransfer> $x
     *
     * @return array<\Orm\Zed\Payment\Persistence\SpySalesPayment>
     */
    public function iterableTest(iterable $x = []): array
    {
        return $x;
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array<string>
     */
    protected function getCountryIso2Codes(iterable $itemTransfers): array
    {
        return array_unique($itemTransfers);
    }

    /**
     * @param class-string<\Propel\Runtime\Map\TableMap> $tableMapClass The name of the table map to add
     *
     * @return void
     */
    public function registerTableMapClass(string $tableMapClass): void
    {
    }

    /**
     * @param string|null $value The value to convert.
     *
     * @return string|null
     */
    public function toPHP(mixed $value): mixed {
        return $value;
    }
}
