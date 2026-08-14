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

if (!\function_exists('MAX_base64EncodeUrlSafe')) {
    require_once dirname(__DIR__, 4) . '/max/Delivery/base64.php';
}

/**
 * Namespaced facade for the procedural delivery library
 * lib/max/Delivery/base64.php. Each static method delegates to the
 * corresponding global function, which remains the canonical
 * implementation on the delivery fast path.
 */
class Base64
{
    public static function MAX_base64EncodeUrlSafe($string)
    {
        return \MAX_base64EncodeUrlSafe($string);
    }

    public static function MAX_base64DecodeUrlSafe($string)
    {
        return \MAX_base64DecodeUrlSafe($string);
    }
}
