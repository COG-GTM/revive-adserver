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

if (!\function_exists('MAX_flashGetFlashObjectExternal')) {
    require_once dirname(__DIR__, 4) . '/max/Delivery/flash.php';
}

/**
 * Namespaced facade for the procedural delivery library
 * lib/max/Delivery/flash.php. Each static method delegates to the
 * corresponding global function, which remains the canonical
 * implementation on the delivery fast path.
 */
class Flash
{
    public static function MAX_flashGetFlashObjectExternal()
    {
        return \MAX_flashGetFlashObjectExternal();
    }

    public static function MAX_flashGetFlashObjectInline()
    {
        return \MAX_flashGetFlashObjectInline();
    }
}
