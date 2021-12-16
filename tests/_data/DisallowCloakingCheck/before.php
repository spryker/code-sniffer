<?php declare(strict_types = 1);

namespace Spryker;

class FixMe
{
    public function test()
    {
        $x = null;
        if (empty($x)) {
        }

        if (!empty($customerBlacklistIds)) {
        }

        if (empty($this->ok())) {
        }

        if (
            !empty($this->foo('x', 'y'))
            || $this->ok()
        ) {
        }

        if (!isset($this->prop)) {
        }

        $x = !empty($this->prop);

        return !empty($x);
    }

    public function complex($successTable)
    {
        return [
            'renderSuccessTable' => empty($successTable->getData()) !== true,
        ];
    }

    public function ok()
    {
        $x = [];
        if (!empty($x['y'])) {
        }
    }
}
