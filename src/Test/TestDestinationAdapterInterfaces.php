<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webfactory\ContentMapping\Test;

use Webfactory\ContentMapping\DestinationAdapter;
use Webfactory\ContentMapping\ProgressListenerInterface;

interface TestDestinationAdapterInterfaces extends DestinationAdapter, ProgressListenerInterface
{

}
