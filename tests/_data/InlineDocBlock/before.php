<?php declare(strict_types = 1);

namespace Spryker;

class FixMe
{
    public function test()
    {
        /**
         * A collection of indexed columns. The key is the column name
         * (concatenated with a comma in the case of multi-col index).
         *
         * @var array<string, array<string>> $_indices
         */
        $_indices = [];

        /*
         * This is not a docblock;
         * But a multiline comment
         */
        $foo = $_indices['x'];

        /** @var string|null $bar */
        $bar = $_indices['y'];

        /* @var string $foo */
        $foo = $_indices['z'];
    }
}
