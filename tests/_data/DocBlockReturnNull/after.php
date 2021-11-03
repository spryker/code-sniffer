<?php declare(strict_types = 1);

namespace Spryker;

class FixMe
{
    /**
     * @return mixed
     */
    public function thisIsFine()
    {
        return null;
    }

    /**
     * @return static|null Missing null here
     */
    public function parse()
    {
        if ($this->foo()) {
            return null;
        }

        return new static();
    }
}
