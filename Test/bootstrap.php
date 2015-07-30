<?php
/*
 * (c) webfactory GmbH <info@webfactory.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

foreach (array(__DIR__ . "/../lib/autoload.php", __DIR__ . "/../vendor/autoload.php") as $file) {
    if (file_exists($file)) {
        $loader = require_once($file);
        if (is_object($loader)) {
            $loader->add('', __DIR__);
        }
        break;
    }
}

if (file_exists(__DIR__ . "/../conf/autoprepend.php")) {
    require_once(__DIR__ . "/../conf/autoprepend.php");
}

// Disable circular reference garbage collection as this
// sometimes leads to crashes (noticed on Windows as well
// as on Ubuntu systems).
gc_disable();
