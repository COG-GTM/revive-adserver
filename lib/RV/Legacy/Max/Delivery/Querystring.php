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

if (!\function_exists('MAX_querystringConvertParams')) {
    require_once dirname(__DIR__, 4) . '/max/Delivery/querystring.php';
}

/**
 * Namespaced facade for the procedural delivery library
 * lib/max/Delivery/querystring.php. Each static method delegates to the
 * corresponding global function, which remains the canonical
 * implementation on the delivery fast path.
 */
class Querystring
{
    public static function MAX_querystringConvertParams()
    {
        return \MAX_querystringConvertParams();
    }

    public static function MAX_querystringGetDestinationUrl($adId = 0, $zoneId = 0)
    {
        return \MAX_querystringGetDestinationUrl($adId, $zoneId);
    }

    public static function MAX_querystringParseStr($qs, &$aArr, $delim = '&')
    {
        return \MAX_querystringParseStr($qs, $aArr, $delim);
    }
}
