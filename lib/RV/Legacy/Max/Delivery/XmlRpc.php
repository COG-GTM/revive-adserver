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

require_once dirname(__DIR__, 4) . '/max/Delivery/XML-RPC.php';

/**
 * Namespaced facade for the procedural delivery library
 * lib/max/Delivery/XML-RPC.php. Each static method delegates to the
 * corresponding global function, which remains the canonical
 * implementation on the delivery fast path.
 */
class XmlRpc
{
    public static function OA_Delivery_XmlRpc_View($params)
    {
        return \OA_Delivery_XmlRpc_View($params);
    }

    public static function OA_Delivery_XmlRpc_SPC($params)
    {
        return \OA_Delivery_XmlRpc_SPC($params);
    }

    public static function OA_Delivery_XmlRpc_View_Max($params)
    {
        return \OA_Delivery_XmlRpc_View_Max($params);
    }

    public static function OA_Delivery_XmlRpc_View_PAN($params)
    {
        return \OA_Delivery_XmlRpc_View_PAN($params);
    }
}
