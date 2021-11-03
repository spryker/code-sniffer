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
     * @return array<string, array<string, int|string|null>>
     */
    public function array(): ?array
    {
        return [];
    }
}
