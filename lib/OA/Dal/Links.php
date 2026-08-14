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

if (!class_exists('Openads_Links', false)) {
    if (false) {
        class Openads_Links extends \RV\Legacy\OA\Dal\Links
        {
        }
    }

    class_alias(\RV\Legacy\OA\Dal\Links::class, 'Openads_Links');
}
