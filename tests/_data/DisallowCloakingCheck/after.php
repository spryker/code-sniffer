<?php declare(strict_types = 1);

namespace Spryker;

class FixMe
{
    public function test()
    {
        $x = null;
        if (!$x) {
        }

        if ($customerBlacklistIds) {
        }

        if (!$this->ok()) {
        }

        if (
            $this->foo('x', 'y')
            || $this->ok()
        ) {
        }

        if (!isset($this->prop)) {
        }

        $x = (bool)$this->prop;

        return (bool)$x;
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
