<?php declare(strict_types = 1);

namespace Spryker;

use Closure;
use Foo\Baz\Exception as SomeAliasedException;
use Foo\Bar\SomeException;
use DomainException;
use LogicException;
use RangeException;
use RuntimeException;
use League\OAuth2\Server\Exception\OAuthServerException;

class FixMe
{
    /**
     * Return a closure that throws a runtime exception.
     *
     * @param bool $throw Whether to throw immediately.
     * @throws \LogicException
     * @throws \RangeException
     * @return \Closure
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

    /**
     * @throws \Foo\Bar\SomeException
     * @throws \Foo\Baz\Exception
     * @return void
     */
    public function complex(): void
    {
        if ($x) {
            throw new SomeException();
        } else {
            throw new SomeAliasedException();
        }
    }

    /**
     * @throws \BadMethodCallException
     * @return void
     */
    public function someException()
    {
        if ($this->something()) {
            throw new \BadMethodCallException();
        }
    }

    /**
     * @throws \League\OAuth2\Server\Exception\OAuthServerException
     * @return void
     */
    public function staticCall(): void
    {
        throw OAuthServerException::accessDenied('baz');

        new Parser();
    }
}
