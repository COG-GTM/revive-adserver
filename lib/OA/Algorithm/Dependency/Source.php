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

namespace OA\Algorithm\Dependency;

use Exception;

/**
 * The Algorithm::Dependency::Source class provides an abstract parent class for
 * implementing sources for the heirachy data the algorithm will use. For an
 * example of an implementation of this, see
 * Algorithm::Dependency::Source::HoA
 *
 * Based on CPAN module:
 * http://search.cpan.org/~adamk/Algorithm-Dependency-1.106/lib/Algorithm/Dependency/Source.pm
 *
 */
abstract class Source
{
    protected bool $loaded = false;

    /**
     * @var array<string, Item>
     */
    protected array $itemsHash = [];

    /**
     * @var array<int, Item>
     */
    protected array $itemsQueue = [];

    /**
     * The load() method is the public method used to actually load the items from
     * their storage location into the the source object. The method will
     * automatically called, as needed, in most circumstances. You would generally
     * only want to use load() manually if you think there may be some uncertainty
     * that the source will load correctly, and want to check it will work.
     *
     * @return boolean  Returns true if the items are loaded successfully,
     *                  or false on error.
     */
    public function load()
    {
        if ($this->loaded) {
            $this->loaded = false;
            $this->itemsQueue = [];
            $this->itemsHash = [];
        }

        $items = $this->_loadItemList();
        if (!$items) {
            return $items;
        }
        if (!is_array($items)) {
            throw new Exception('_loadItemList() did not return an Algorithm_Dependency_Item array');
        }

        foreach ($items as $item) {
            if (isset($this->itemsHash[$item->getId()])) {
                // duplicate entry
                return false;
            }
            $this->itemsHash[$item->getId()] = $item;
            $this->itemsQueue[] = $item;
        }
        $this->loaded = true;
        return true;
    }

    /**
     * The getItem() method fetches and returns the item object specified by the
     * name argument.
     *
     * @param mixed $id
     * @return Item|false  Returns an Item or false if it doesn't exist in the source
     */
    public function getItem($id): Item|false
    {
        if (!$this->checkLoaded() || !isset($this->itemsHash[$id])) {
            return false;
        }
        return $this->itemsHash[$id];
    }

    /**
     * The getItems() method returns, as a list of objects, all of the items
     * contained in the source. The item objects will be returned in the same order
     * as that in the storage location.
     *
     * @return array<int, Item>|false  A list of Item objects on success, or false on error.
     */
    public function getItems(): array|false
    {
        if (!$this->checkLoaded()) {
            return false;
        }
        return $this->itemsQueue;
    }

    /**
     * The getItemsIds() method returns, as a list of items ids, all of the items
     * contained in the source. The item objects will be returned in the same order
     * as that in the storage location.
     *
     * @return array<int, string>|false  Returns an array of ids on success, or false on error
     */
    public function getItemsIds(): array|false
    {
        if (!$this->checkLoaded()) {
            return false;
        }
        $itemsIds = [];
        foreach ($this->itemsQueue as $item) {
            $itemsIds[] = $item->getId();
        }
        return $itemsIds;
    }

    /**
     * By default, we are leniant with missing dependencies if the item is never
     * used. For systems where having a missing dependency can be very bad, the
     * getMissingDependencies() method checks all Items to make sure their
     * dependencies exist.
     *
     * If there are any missing dependencies, returns an array of their ids. If
     * there are no missing dependencies, returns an empty array. Returns false
     * on error.
     *
     * @return array<int|string, string>|false
     */
    public function getMissingDependencies(): array|false
    {
        if (!$this->checkLoaded()) {
            return false;
        }
        $dependencies = [];
        foreach ($this->itemsQueue as $item) {
            $dependencies = array_merge($dependencies, $item->getDependencies());
        }
        return array_diff($dependencies, array_keys($this->itemsHash));
    }

    /**
     * Lazy loading
     *
     * @return boolean  True if data is already loaded or was loaded succesfully or false
     *                  if any error occurred.
     */
    public function checkLoaded()
    {
        if (!$this->loaded) {
            return $this->load();
        }
        return true;
    }

    /**
     * Catch unimplemented methods in subclasses
     *
     * @return array<int, Item>|false
     */
    abstract public function _loadItemList();
}
