<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Test\Fixture;

class DocBlockParam
{
    /**
     * Only the lossy (array) param is documented; the redundant scalar is omitted - allowed.
     *
     * @param array<string> $sources
     *
     * @return void
     */
    public function arrayOnlyDocumented(array $sources, string $target): void
    {
    }

    /**
     * All params documented, including a redundant scalar - still allowed (backwards compatibility).
     *
     * @param array<string> $sources
     * @param string $target
     *
     * @return void
     */
    public function allDocumented(array $sources, string $target): void
    {
    }

    /**
     * A `@param` referencing a non-existent parameter must be reported.
     *
     * @param string $nope
     *
     * @return void
     */
    public function extraParam(string $target): void
    {
    }

    /**
     * An array param must keep its `@param` (element type cannot be expressed natively).
     *
     * @param string $target
     *
     * @return void
     */
    public function arrayUndocumented(string $target, array $rows): void
    {
    }

    /**
     * An untyped param must keep its `@param` (otherwise no type information at all).
     *
     * @param string $target
     *
     * @return void
     */
    public function untypedUndocumented(string $target, $thing): void
    {
    }

    /**
     * A no-argument method must not carry a stray `@param`.
     *
     * @param string $x
     *
     * @return void
     */
    public function noArgStrayParam(): void
    {
    }

    /**
     * All params are fully covered by native type hints, so every redundant `@param` may be omitted.
     *
     * @return void
     */
    public function allRedundantOmitted(string $a, \DateTimeInterface $b): void
    {
    }

    /**
     * A union type that includes `array` still hides the element type/shape, so its `@param` is
     * required even though the union also has a fully-expressive scalar member.
     *
     * @param string $target
     *
     * @return void
     */
    public function unionArrayUndocumented(string $target, array|string $rows): void
    {
    }

    /**
     * The same union is allowed when documented (backwards compatibility).
     *
     * @param array<string>|string $rows
     *
     * @return void
     */
    public function unionArrayDocumented(array|string $rows): void
    {
    }
}
