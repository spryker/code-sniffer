<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Bundle\Business;

use \DateTime;
use \DateTimeZone;

class UseWithLeadingBackslashFail
{

    /**
     * @param \DateTimeZone $dtz
     *
     * @return \DateTime
     */
    public function test(DateTimeZone $dtz)
    {
        return new DateTime(null, $dtz);
    }

}
