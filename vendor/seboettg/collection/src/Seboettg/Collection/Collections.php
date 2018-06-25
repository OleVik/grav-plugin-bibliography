<?php

namespace Seboettg\Collection;


/**
 * Class Collections
 * @package Seboettg\Collection
 *
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class Collections
{
    /**
     * Sorts the specified list according to the order induced by the specified comparator. All elements in the list
     * must be mutually comparable.
     *
     * @param ArrayList $list
     * @param Comparator $comparator
     * @return ArrayList
     */
    public static function sort(ArrayList &$list, Comparator $comparator)
    {
        $array = $list->toArray();
        usort($array, [$comparator, "compare"]);
        $list->replace($array);
        return $list;
    }
}
