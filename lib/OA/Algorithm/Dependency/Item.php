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

class Item
{
    protected string $id;

    /**
     * @var array<int, string>
     */
    protected array $depends;

    /**
     * @param array<int, string> $depends
     */
    public function __construct(string $id, array $depends = [])
    {
        $this->id = $id;
        $this->depends = $depends;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return array<int, string>
     */
    public function getDependencies(): array
    {
        return $this->depends;
    }
}
