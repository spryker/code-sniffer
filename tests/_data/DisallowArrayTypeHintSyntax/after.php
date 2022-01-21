<?php declare(strict_types = 1);

namespace Spryker;

class FixMe
{
    /**
     * @return array<int>
     */
    protected function getSimpleInts(): array
    {
        return $this->foo();
    }

    /**
     * @return \ArrayObject<string>
     */
    protected function getSimpleStringCollection()
    {
        return $this->foo();
    }

    /**
     * @return \Iterator<int>
     */
    protected function getSimpleIntCollection(): Iterator
    {
        return $this->foo();
    }

    /**
     * @return \Iterator|\Generated\Shared\Transfer\EventEntityTransfer[][]
     */
    protected function createEventResourceQueryContainerPluginIterator(): Iterator
    {
        return new EventResourceQueryContainerPluginIterator();
    }
}
