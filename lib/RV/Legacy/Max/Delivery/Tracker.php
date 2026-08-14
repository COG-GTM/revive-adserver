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

if (!\function_exists('MAX_trackerbuildJSVariablesScript')) {
    require_once dirname(__DIR__, 4) . '/max/Delivery/tracker.php';
}

/**
 * Namespaced facade for the procedural delivery library
 * lib/max/Delivery/tracker.php. Each static method delegates to the
 * corresponding global function, which remains the canonical
 * implementation on the delivery fast path.
 */
class Tracker
{
    public static function MAX_trackerbuildJSVariablesScript($trackerid, $conversionInfo, $trackerJsCode = null)
    {
        return \MAX_trackerbuildJSVariablesScript($trackerid, $conversionInfo, $trackerJsCode);
    }

    public static function MAX_trackerCheckForValidAction($trackerid)
    {
        return \MAX_trackerCheckForValidAction($trackerid);
    }

    public static function _getActionTypes()
    {
        return \_getActionTypes();
    }

    public static function _getTrackerTypes()
    {
        return \_getTrackerTypes();
    }
}
