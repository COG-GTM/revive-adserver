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

require_once __DIR__ . '/../../../RV/Legacy/OA/DB/DataObject/Generator.php';

if (!class_exists('OA_DB_DataObject_Generator', false)) {
    class_alias(\RV\Legacy\OA\DB\DataObject\Generator::class, 'OA_DB_DataObject_Generator');
}

if (false) {
    class OA_DB_DataObject_Generator extends \RV\Legacy\OA\DB\DataObject\Generator
    {
    }
}
