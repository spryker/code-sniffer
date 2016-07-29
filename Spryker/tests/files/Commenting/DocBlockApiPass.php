<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Bundle\Business;

class DocBlockApiPass
{

    /**
     * LongDescription
     *
     * ShortDescription
     *
     * @api
     *
     * @param int $foo
     *
     * @return void
     */
    public function methodWithoutApiAnnotation($foo)
    {

    }

    /**
     * @return void
     */
    protected function doSomething()
    {

    }

}
