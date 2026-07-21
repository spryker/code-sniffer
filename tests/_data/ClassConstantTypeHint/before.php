<?php

namespace App;

class Foo
{
    public const int TYPED_INT = 1;

    /**
     * @var list<string>
     */
    protected const array TYPED_ARRAY = ['a', 'b'];

    public const UNTYPED_SCALAR = 1;

    protected const UNTYPED_MULTI_A = 1, UNTYPED_MULTI_B = 2;
}
