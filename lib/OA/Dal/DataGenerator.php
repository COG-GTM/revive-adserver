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

if (!class_exists('DataGenerator', false)) {
    if (false) {
        class DataGenerator extends \RV\Legacy\OA\Dal\DataGenerator
        {
        }
    }

    class_alias(\RV\Legacy\OA\Dal\DataGenerator::class, 'DataGenerator');
}
