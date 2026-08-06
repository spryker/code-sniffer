<?php

namespace App;

class Foo
{
    /**
     * @var \ArrayObject<int, \Generated\Shared\Transfer\ServiceTransfer>|mixed
     */
    protected mixed $keptGeneric;

    /**
     * @var array{sku: string, quantity: int}|mixed
     */
    protected mixed $keptArrayShape;

    /**
     * @var string
     */
    protected string $restatedTypeHint = '';

    /**
     * @var string|mixed
     */
    protected mixed $mixedUnionWithoutDetail;
}
