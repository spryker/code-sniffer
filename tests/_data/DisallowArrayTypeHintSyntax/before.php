<?php declare(strict_types = 1);

namespace Spryker;

class FixMe
{
    /**
     * @var \ArrayObject|string[]
     */
    protected $prop;

    /**
     * @var \ArrayObject|string[] Some description.
     */
    protected $propWithDescription;

    /**
     * @param string[] $var
     *
     * @return int[]
     */
    protected function getSimpleInts(array $var): array
    {
        return $this->foo();
    }

    /**
     * @param \ArrayObject|string[] $var Some comment.
     *
     * @return \ArrayObject|string[]
     */
    protected function getSimpleStringCollection($var)
    {
        return $this->foo();
    }

    /**
     * @return \ArrayObject|array<string>
     */
    protected function getArrayObjectOrArray()
    {
        return $this->foo();
    }

    /**
     * @return \ArrayObject<string>|int[]
     */
    protected function getArrayObjectOfStringsOrArrayOfInts()
    {
        return $this->foo();
    }

    /**
     * @return \ArrayObject|string[]|null
     */
    protected function getSimpleStringCollectionOrNull(): ?\ArrayObject
    {
        return $this->foo();
    }

    /**
     * @return \Iterator|int[]
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

    /**
     * @return void
     */
    protected function inlineDocBlock()
    {
        /** @var \ArrayObject|string[] $bar */
        $bar = $this->foo();

        /**
         * @var \ArrayObject|string[] $bar
         */
        $bar = $this->foo();
    }
}
