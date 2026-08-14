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

require_once dirname(__DIR__, 4) . '/max/Delivery/cookie.php';

/**
 * Namespaced facade for the procedural delivery library
 * lib/max/Delivery/cookie.php. Each static method delegates to the
 * corresponding global function, which remains the canonical
 * implementation on the delivery fast path.
 */
class Cookie
{
    public static function MAX_cookieAdd($name, $value, $expire = 0)
    {
        return \MAX_cookieAdd($name, $value, $expire);
    }

    public static function MAX_cookieSetViewerIdAndRedirect($viewerId)
    {
        return \MAX_cookieSetViewerIdAndRedirect($viewerId);
    }

    public static function _getTimeThirtyDaysFromNow()
    {
        return \_getTimeThirtyDaysFromNow();
    }

    public static function _getTimeYearFromNow()
    {
        return \_getTimeYearFromNow();
    }

    public static function _getTimeYearAgo()
    {
        return \_getTimeYearAgo();
    }

    public static function MAX_cookieUnpackCapping()
    {
        return \MAX_cookieUnpackCapping();
    }

    public static function _isBlockCookie($cookieName)
    {
        return \_isBlockCookie($cookieName);
    }

    public static function MAX_cookieGetUniqueViewerId($create = true)
    {
        return \MAX_cookieGetUniqueViewerId($create);
    }

    public static function MAX_cookieGetCookielessViewerID()
    {
        return \MAX_cookieGetCookielessViewerID();
    }

    public static function MAX_Delivery_cookie_cappingOnRequest()
    {
        return \MAX_Delivery_cookie_cappingOnRequest();
    }

    public static function MAX_Delivery_cookie_setCapping($type, $id, $block = 0, $cap = 0, $sessionCap = 0)
    {
        return \MAX_Delivery_cookie_setCapping($type, $id, $block, $cap, $sessionCap);
    }

    public static function MAX_cookieClientCookieSet($name, $value, $expires, $path = '/', $domain = null, $secure = null, $httpOnly = false, $sameSite = 'none')
    {
        return \MAX_cookieClientCookieSet($name, $value, $expires, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    public static function MAX_cookieClientCookieUnset($name)
    {
        return \MAX_cookieClientCookieUnset($name);
    }

    public static function MAX_cookieClientCookieFlush()
    {
        return \MAX_cookieClientCookieFlush();
    }
}
