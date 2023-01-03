<?php declare(strict_types = 1);

namespace Spryker;

class FixMe
{
    /**
     * @param string|null $string
     * @return string|null
     */
    public function dontTouchMe(?string $string = null): ?string
    {
        return $string;
    }

    /**
     * @param string|null $string
     * @return string
     */
    public function missing(?string $string = null): ?string
    {
        return $string;
    }

    /**
     * @param string|null $string
     * @return string|null
     */
    public function superfluous(?string $string = null): string
    {
        return $string;
    }

    /**
     * @param string|null $string
     * @return string|int|null
     */
    public function specialMixed(?string $string = null): mixed
    {
        return $string;
    }

    /**
     * @param string|null $string
     * @return string|int
     */
    public function specialMixedNotNullable(?string $string = null): mixed
    {
        return $string;
    }

    /**
     * @return array<string, array<string, int|string|null>>
     */
    public function array(): ?array
    {
        return [];
    }
}
