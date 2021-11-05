<?php declare(strict_types = 1);

namespace Spryker;

class FixMe
{
    public const FOO = 'FOO';
    protected const OUTPUT_NORMAL = 'OUTPUT_NORMAL';

    public function allGood($options = self::OUTPUT_NORMAL)
    {
        if (static::FOO) {
            return static::merge();
        }
    }

    public function fixMe()
    {
        if (static::FOO) {
            return static::merge();
        }
    }

    public static function parse()
    {
        static $defaults = [
            static::FOO => null, // breaks code, needs to be refactored to class prop
        ];

        return new static($defaults);
    }
}
