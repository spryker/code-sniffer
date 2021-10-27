<?php declare(strict_types = 1);

namespace Spryker;

use Closure;

class FixMe
{
    /**
     */
    public function aVoidOne()
    {
    }

    /**
     */
    public function anotherVoidOne(): void
    {
    }

    /**
     */
    public function alsoVoidOne($x): void
    {
        if ($x) {
            return;
        }

        $this->anotherVoidOne();
    }

    /**
     * @return \Closure
     */
    public function foo(): Closure
    {
        /**
         * @return void
         */
        $bar = static function (): void {
        };

        return $bar;
    }
}
