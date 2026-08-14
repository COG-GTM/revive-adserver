<?php

/*
+---------------------------------------------------------------------------+
| Revive Adserver                                                           |
| http://www.revive-adserver.com                                            |
|                                                                           |
| Copyright: See the COPYRIGHT.txt file.                                    |
| License: GPLv2 or later, see the LICENSE.txt file.                        |
+---------------------------------------------------------------------------+
*/

namespace RV\Legacy\Max\Delivery;

if (!\function_exists('MAX_javascriptToHTML')) {
    require_once dirname(__DIR__, 4) . '/max/Delivery/javascript.php';
}

/**
 * Namespaced facade for the procedural delivery library
 * lib/max/Delivery/javascript.php. Each static method delegates to the
 * corresponding global function, which remains the canonical
 * implementation on the delivery fast path.
 */
class Javascript
{
    public static function MAX_javascriptToHTML($string, $varName, $output = true, $localScope = true)
    {
        return \MAX_javascriptToHTML($string, $varName, $output, $localScope);
    }

    public static function MAX_javascriptEncodeJsonField($string)
    {
        return \MAX_javascriptEncodeJsonField($string);
    }
}
