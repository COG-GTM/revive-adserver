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

if (!isset($GLOBALS['_MAX']['FILES']['/lib/max/Delivery/common.php'])) {
    // Required by PHP5.1.2
    require_once MAX_PATH . '/lib/max/Delivery/common.php';
}

/**
 * Register an array of variable names in the global scope
 *
 * Note: This is now a wrapper to the delivery engine's equivalent function
 *
 */
function phpAds_registerGlobal(...$args)
{
    MAX_commonRegisterGlobalsArray($args);
}

/**
 * This function takes an array of variable names
 * and makes them available in the global scope
 *
 * $_POST values take precedence over $_GET values
 *
 */
function phpAds_registerGlobalUnslashed(...$args)
{
    $request = [];
    foreach ($args as $key) {
        $GLOBALS[$key] = $request[$key] = $_GET[$key] ?? $_POST[$key] ?? null;
    }
    return $request;
}

/**
 * Returns the "returnurl" request parameter if it points to a local page,
 * otherwise the given default. $_POST takes precedence over $_GET.
 *
 * @param string $default
 * @return string
 */
function phpAds_getReturnUrl($default)
{
    $url = $_POST['returnurl'] ?? $_GET['returnurl'] ?? null;

    return is_string($url) && phpAds_isLocalUrl($url) ? $url : $default;
}

/**
 * Checks that a URL is relative to the current host: no scheme, no host,
 * no protocol-relative prefix and no characters browsers may normalise
 * into one.
 *
 * @param string $url
 * @return bool
 */
function phpAds_isLocalUrl($url)
{
    if ($url === '' || preg_match('#[\\\\\x00-\x20\x7f]#', $url) || str_starts_with($url, '//')) {
        return false;
    }

    $parts = parse_url($url);

    return $parts !== false && !isset($parts['scheme']) && !isset($parts['host']);
}
