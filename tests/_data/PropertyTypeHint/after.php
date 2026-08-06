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

    protected string $restatedTypeHint = '';

    protected mixed $mixedUnionWithoutDetail;
}
