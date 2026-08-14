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
 * Compatibility bridge for the PEAR-style class names of the lib/OA/Algorithm
 * subtree, which has been converted to the OA\Algorithm PSR-4 namespace.
 *
 * The aliases are created lazily, so the namespaced classes are only loaded
 * when a legacy name is actually used. See docs/psr4-migration.md.
 */

$aAliases = [
    'OA_Algorithm_Dependency' => OA\Algorithm\Dependency::class,
    'OA_Algorithm_Dependency_Item' => OA\Algorithm\Dependency\Item::class,
    'OA_Algorithm_Dependency_Ordered' => OA\Algorithm\Dependency\Ordered::class,
    'OA_Algorithm_Dependency_Source' => OA\Algorithm\Dependency\Source::class,
    'OA_Algorithm_Dependency_Source_HoA' => OA\Algorithm\Dependency\Source\HoA::class,
];

spl_autoload_register(static function (string $class) use ($aAliases): void {
    if (isset($aAliases[$class])) {
        class_alias($aAliases[$class], $class);
    }
});
