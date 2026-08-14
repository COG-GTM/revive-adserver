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

namespace OA\Algorithm\Dependency\Source;

use OA\Algorithm\Dependency\Item;
use OA\Algorithm\Dependency\Source;

/**
 * Source for a HASH of ARRAYs
 *
 * Based on CPAN class:
 * http://search.cpan.org/~adamk/Algorithm-Dependency-1.106/lib/Algorithm/Dependency/Source/HoA.pm
 *
 * Algorithm::Dependency::Source::HoA implements a
 * Algorithm::Dependency::Source where the items names are provided
 * in the most simple form, an array.
 *
 * The basic data structure:
 * $deps = array {
 *     foo => array('bar', 'baz'),
 *     bar => array(),
 *     baz => array('bar'),
 *     bar, // same as: bar => array()
 * }
 *
 * Create the source from it
 * $source = new OA\Algorithm\Dependency\Source\HoA($deps);
 *
 */
class HoA extends Source
{
    /**
     * @var array<int|string, array<int, string>|string>
     */
    private array $hash = [];

    /**
     * @param array<int|string, array<int, string>|string> $deps
     */
    public function __construct(array $deps = [])
    {
        $this->hash = $deps;
    }

    /**
     * @return array<int, Item>
     */
    public function _loadItemList(): array
    {
        $items = [];
        foreach ($this->hash as $id => $dependency) {
            if (!is_array($dependency)) {
                $id = $dependency;
                $dependency = [];
            }
            $items[] = new Item((string) $id, $dependency);
        }
        return $items;
    }
}
