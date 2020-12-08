<?php declare(strict_types = 1);

namespace Spryker;

use Closure;
use DomainException;
use LogicException;
use RangeException;
use RuntimeException;

class InvalidThrowSniff
{
    /**
     * Return a closure that throws a runtime exception.
     *
     * @param bool $throw Whether to throw immediately.
     * @return Closure
     */
    public function foo(bool $throw): Closure
    {
        if ($throw) {
            throw new RangeException();
        }

        $foo = fn($value) => $value === true ? throw new DomainException() : null;

        $bar = static function (): void {
            throw new RuntimeException();
        };

        throw new LogicException();
    }
}
