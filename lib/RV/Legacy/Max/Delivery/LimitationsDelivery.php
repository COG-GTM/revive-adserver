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

require_once dirname(__DIR__, 4) . '/max/Delivery/limitations.delivery.php';

/**
 * Namespaced facade for the procedural delivery library
 * lib/max/Delivery/limitations.delivery.php. Each static method delegates to the
 * corresponding global function, which remains the canonical
 * implementation on the delivery fast path.
 */
class LimitationsDelivery
{
    public static function MAX_limitationsMatchStringClientGeo($paramName, $limitation, $op, $aParams = [])
    {
        return \MAX_limitationsMatchStringClientGeo($paramName, $limitation, $op, $aParams);
    }

    public static function MAX_limitationsMatchString($paramName, $limitation, $op, $aParams = [], $namespace = 'CLIENT',)
    {
        return \MAX_limitationsMatchString($paramName, $limitation, $op, $aParams, $namespace);
    }

    public static function MAX_limitationsMatchNumericValue($value, $limitation, $op)
    {
        return \MAX_limitationsMatchNumericValue($value, $limitation, $op);
    }

    public static function MAX_limitationsMatchNumeric($paramName, $limitation, $op, $aParams = [], $namespace = 'CLIENT',)
    {
        return \MAX_limitationsMatchNumeric($paramName, $limitation, $op, $aParams, $namespace);
    }

    public static function MAX_limitationMatchNumeric($paramName, $limitation, $op, $aParams = [], $namespace = 'CLIENT')
    {
        return \MAX_limitationMatchNumeric($paramName, $limitation, $op, $aParams, $namespace);
    }

    public static function MAX_limitationsMatchStringValue($value, $limitation, $op)
    {
        return \MAX_limitationsMatchStringValue($value, $limitation, $op);
    }

    public static function MAX_limitationsMatchArrayClientGeo($paramName, $limitation, $op, &$aParams = [])
    {
        return \MAX_limitationsMatchArrayClientGeo($paramName, $limitation, $op, $aParams);
    }

    public static function MAX_limitationsMatchArray($paramName, $limitation, $op, $aParams = [], $namespace = 'CLIENT')
    {
        return \MAX_limitationsMatchArray($paramName, $limitation, $op, $aParams, $namespace);
    }

    public static function MAX_limitationsMatchArrayValue($value, $limitation, $op)
    {
        return \MAX_limitationsMatchArrayValue($value, $limitation, $op);
    }

    public static function MAX_limitationsIsOperatorSimple($op)
    {
        return \MAX_limitationsIsOperatorSimple($op);
    }

    public static function MAX_limitationsIsOperatorContains($op)
    {
        return \MAX_limitationsIsOperatorContains($op);
    }

    public static function MAX_limitationsIsOperatorNumeric($op)
    {
        return \MAX_limitationsIsOperatorNumeric($op);
    }

    public static function MAX_limitationsIsOperatorRegexp($op)
    {
        return \MAX_limitationsIsOperatorRegexp($op);
    }

    public static function MAX_limitationsIsOperatorPositive($op)
    {
        return \MAX_limitationsIsOperatorPositive($op);
    }

    public static function MAX_limitationsGetAOperationsEquality($oPlugin)
    {
        return \MAX_limitationsGetAOperationsEquality($oPlugin);
    }

    public static function MAX_limitationsGetAOperationsForNumeric($oPlugin)
    {
        return \MAX_limitationsGetAOperationsForNumeric($oPlugin);
    }

    public static function MAX_limitationsGetAOperationsForString($oPlugin)
    {
        return \MAX_limitationsGetAOperationsForString($oPlugin);
    }

    public static function MAX_stringContains($sString, $sToken)
    {
        return \MAX_stringContains($sString, $sToken);
    }

    public static function MAX_limitationsGetAFromS($sString)
    {
        return \MAX_limitationsGetAFromS($sString);
    }

    public static function MAX_limitationsGetSFromA($aArray)
    {
        return \MAX_limitationsGetSFromA($aArray);
    }

    public static function MAX_limitationsGetPreprocessedString($sString)
    {
        return \MAX_limitationsGetPreprocessedString($sString);
    }

    public static function MAX_limitationsGetPreprocessedArray($aArray)
    {
        return \MAX_limitationsGetPreprocessedArray($aArray);
    }

    public static function MAX_limitationsGetCountry($aData)
    {
        return \MAX_limitationsGetCountry($aData);
    }

    public static function MAX_limitationsSetCountry(&$aData, $sCountry)
    {
        return \MAX_limitationsSetCountry($aData, $sCountry);
    }

    public static function _safe_preg_match($limitation, $value)
    {
        return \_safe_preg_match($limitation, $value);
    }

    public static function _getSRegexpDelimited($sRawRegexp)
    {
        return \_getSRegexpDelimited($sRawRegexp);
    }

    public static function MAX_ipWithLastComponentReplacedByStar($ip)
    {
        return \MAX_ipWithLastComponentReplacedByStar($ip);
    }

    public static function MAX_ipContainsStar($ip)
    {
        return \MAX_ipContainsStar($ip);
    }
}
