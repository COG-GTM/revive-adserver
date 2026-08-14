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

require_once __DIR__ . '/../../RV/Legacy/OA/Dal/Links.php';

// Back-compat shim: Openads_Links now lives in \RV\Legacy\OA\Dal\Links.
// The dead declaration below is never executed; it exists so that Composer's
// classmap generator indexes the legacy name against this file.
if (false) {
    class Openads_Links extends \RV\Legacy\OA\Dal\Links {}
}

if (!class_exists('Openads_Links', false)) {
    class_alias(\RV\Legacy\OA\Dal\Links::class, 'Openads_Links');
}
