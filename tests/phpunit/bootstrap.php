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
 * Mirrors the environment set up by tests/init.php for the SimpleTest
 * runner (constants, PEAR include path, test config, UTC timezone),
 * without the SimpleTest-specific configuration.
 */

declare(strict_types=1);

$reviveRoot = dirname(__DIR__, 2);

require_once $reviveRoot . '/lib/vendor/autoload.php';

define('TEST_ENVIRONMENT_RUNNING', true);

require_once $reviveRoot . '/init-parse.php';
require_once $reviveRoot . '/constants.php';
require_once $reviveRoot . '/memory.php';
require_once $reviveRoot . '/variables.php';

setupConstants();
setupConfigVariables();

require_once 'PEAR.php';

$GLOBALS['_MAX']['PREF'] = [];
