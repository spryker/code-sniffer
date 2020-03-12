<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Bundle\Business\Foo;

use Spryker\Zed\Bundle\Business\Foo\Bar\Baz\Foo;
use Spryker\Zed\Bundle\Business\Foo\BarBaz;
use Spryker\Zed\Bundle\Business\FooBar\Bar;
use Spryker\Zed\Bundle\Business\FooBarBaz;

class UseInAlphabeticalOrderPass
{

    /**
     * @return bool
     */
    public function method()
    {
        $foo = new Foo();
        $bar = new Bar();
        $barbaz = new BarBaz();
        $foobarbaz = new FooBarBaz();

        return isset($foo, $bar, $barbaz, $foobarbaz);
    }

}
