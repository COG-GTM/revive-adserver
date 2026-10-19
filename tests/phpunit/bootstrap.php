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

/**
 * Bootstrap for the PHPUnit test suites.
 *
 * Only sets up what self-contained unit tests need (autoloader, MAX_PATH and
 * the PEAR include path) - no configuration file or database is required.
 */

require_once dirname(__DIR__, 2) . '/lib/vendor/autoload.php';

if (!defined('MAX_PATH')) {
    define('MAX_PATH', dirname(__DIR__, 2));
}

require_once MAX_PATH . '/variables.php';

setupIncludePath();
