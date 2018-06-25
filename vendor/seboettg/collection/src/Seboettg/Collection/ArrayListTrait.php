<?php
/**
 * Created by PhpStorm.
 * User: seboettg
 * Date: 09.05.18
 * Time: 17:50
 */

namespace Seboettg\Collection;


trait ArrayListTrait
{
    /**
     * internal array
     *
     * @var array
     */
    protected $array;

    /**
     * flush array list
     *
     * @return $this
     */
    public function clear()
    {
        $this->array = [];
        return $this;
    }

    /**
     * returns element with key $key
     * @param $key
     * @return mixed|null
     */
    public function get($key)
    {
        return isset($this->array[$key]) ? $this->array[$key] : null;
    }

    /**
     * Returns the value of the array element that's currently being pointed to by the
     * internal pointer. It does not move the pointer in any way. If the
     * internal pointer points beyond the end of the elements list or the array is
     * empty, current returns false.
     *
     * @return mixed|false
     */
    public function current()
    {
        return current($this->array);
    }

    /**
     * Advance the internal array pointer of an array.
     * Returns the array value in the next place that's pointed to by the
     * internal array pointer, or false if there are no more elements.
     *
     * @return mixed|false
     */
    public function next()
    {
        return next($this->array);
    }

    /**
     * Rewind the internal array pointer.
     * Returns the array value in the previous place that's pointed to by
     * the internal array pointer, or false if there are no more
     *
     * @return mixed|false
     */
    public function prev()
    {
        return prev($this->array);
    }

    /**
     * Inserts or replaces the element at the specified position in this list with the specified element.
     *
     * @param $key
     * @param $element
     * @return $this
     */
    public function set($key, $element)
    {
        $this->array[$key] = $element;
        return $this;
    }

    /**
     * overrides contents of ArrayList with the contents of $array
     * @param array $array
     * @return $this
     */
    public function setArray(array $array)
    {
        return $this->replace($array);
    }

    /**
     * Appends the specified element to the end of this list.
     *
     * @param $element
     * @return $this
     */
    public function append($element)
    {
        $this->array[] = $element;
        return $this;
    }

    /**
     * Inserts the specified element at the specified position in this list. If an other element already exist at the
     * specified position the affected positions will transformed into a numerated array. As well the existing element
     * as the specified element will be appended to this array.
     *
     * @param $key
     * @param $element
     * @return $this
     */
    public function add($key, $element)
    {

        if (!array_key_exists($key, $this->array)) {
            $this->array[$key] = $element;
        } elseif (is_array($this->array[$key])) {
            $this->array[$key][] = $element;
        } else {
            $this->array[$key] = [$this->array[$key], $element];
        }

        return $this;
    }

    /**
     * Removes the element at the specified position in this list.
     *
     * @param $key
     * @return $this
     */
    public function remove($key)
    {
        unset($this->array[$key]);
        return $this;
    }

    /**
     * Returns true if an element exists on the specified position.
     *
     * @param mixed $key
     * @return bool
     */
    public function hasKey($key)
    {
        return array_key_exists($key, $this->array);
    }

    /**
     * Returns true if the specified value exists in this list. Uses PHP's array_search function
     * @link http://php.net/manual/en/function.array-search.php
     *
     * @param string $value
     *
     * @return mixed
     */
    public function hasValue($value)
    {
        $result = array_search($value, $this->array, true);
        return ($result !== false);
    }

    /**
     * replaces this list by the specified array
     * @param array $data
     *
     * @return ArrayList
     */
    public function replace(array $data)
    {
        $this->array = $data;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->array);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset The offset to retrieve.
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return isset($this->array[$offset]) ? $this->array[$offset] : null;
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     */
    public function offsetSet($offset, $value)
    {
        $this->array[$offset] = $value;
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param  mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->array[$offset]);
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset The offset to unset.
     */
    public function offsetUnset($offset)
    {
        unset($this->array[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->array;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return count($this->array);
    }

    /**
     * Shuffles this list (randomizes the order of the elements in). It uses the PHP function shuffle
     * @see http://php.net/manual/en/function.shuffle.php
     * @return $this
     */
    public function shuffle() {
        shuffle($this->array);
        return $this;
    }

    /**
     * returns a clone of this ArrayList, filtered by the given closure function
     * @param \Closure $closure
     * @return ArrayList
     */
    public function filter(\Closure $closure)
    {
        return new self(array_filter($this->array, $closure));
    }

    /**
     * returns a clone of this ArrayList, filtered by the given array keys
     * @param array $keys
     * @return ArrayList
     */
    public function filterByKeys(array $keys)
    {
        return new ArrayList(
            array_filter($this->array, function($key) use ($keys) {
                return array_search($key, $keys) !== false;
            }, ARRAY_FILTER_USE_KEY)
        );
    }
}
