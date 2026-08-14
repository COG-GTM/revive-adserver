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

require_once dirname(__DIR__, 4) . '/max/Delivery/remotehost.php';

/**
 * Namespaced facade for the procedural delivery library
 * lib/max/Delivery/remotehost.php. Each static method delegates to the
 * corresponding global function, which remains the canonical
 * implementation on the delivery fast path.
 */
class RemoteHost
{
    public static function MAX_remotehostSetInfo($run = false)
    {
        return \MAX_remotehostSetInfo($run);
    }

    public static function MAX_remotehostProxyLookup()
    {
        return \MAX_remotehostProxyLookup();
    }

    public static function MAX_remotehostSetRealIpAddress()
    {
        return \MAX_remotehostSetRealIpAddress();
    }

    public static function MAX_remotehostReverseLookup()
    {
        return \MAX_remotehostReverseLookup();
    }

    public static function MAX_remotehostSetGeoInfo()
    {
        return \MAX_remotehostSetGeoInfo();
    }

    public static function MAX_remotehostAnonymise()
    {
        return \MAX_remotehostAnonymise();
    }

    public static function MAX_remotehostPrivateAddress($ip)
    {
        return \MAX_remotehostPrivateAddress($ip);
    }

    public static function MAX_remotehostMatchSubnet($ip, $net, $mask)
    {
        return \MAX_remotehostMatchSubnet($ip, $net, $mask);
    }
}
