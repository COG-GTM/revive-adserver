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

require_once __DIR__ . '/../../RV/Legacy/OA/Dal/DataGenerator.php';

// Back-compat shim: DataGenerator now lives in \RV\Legacy\OA\Dal\DataGenerator.
// The dead declaration below is never executed; it exists so that Composer's
// classmap generator indexes the legacy name against this file.
if (false) {
    class DataGenerator extends \RV\Legacy\OA\Dal\DataGenerator {}
}

if (!class_exists('DataGenerator', false)) {
    class_alias(\RV\Legacy\OA\Dal\DataGenerator::class, 'DataGenerator');
}
