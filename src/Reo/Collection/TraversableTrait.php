<?php
namespace Reo\Collection;

/**
 * Traversable Trait
 *
 * Trait that implements ArrayAccess, Countable and IteratorAggregate
 *
 * This file is part of the Reo Collection Component.
 * Copyright (c) Schuyler W Langdon.
 *
 * For the full copyright and license information, please see the LICENSE
 * file that was distributed with this source code.
 */

trait TraversableTrait
{
    protected $items = [];

    public function get($id)
    {
        return isset($this->items[$id]) ? $this->items[$id] : null;
    }

    public function set($id, $value)
    {
        $this->items[$id] = $value;
    }

    public function setItemKey($id, $key, $value)
    {
        $this->items[$id][$key] = $value;
    }

    public function has($id)
    {
        return isset($this->items[$id]) || array_key_exists($id, $this->items);
    }

    public function remove($id)
    {
        if (array_key_exists($id, $this->items)) {
            unset($this->items[$id]);
        }
    }

    public function setItems(array $items)
    {
        $this->items = $items;
    }

    public function replace(array $items)
    {
        $this->items = $items;
    }

    public function add(array $items)
    {
        $this->items = array_replace($this->items, $items);
    }

    public function toArray()
    {
        return $this->items;
    }

    public function all()
    {
        return $this->toArray();
    }

/**
 * Magical Stuff
 */
    //implement ArrayAccess
    public function offsetSet($k, $v)
    {
        empty($k) ? $this->items[] = $v : $this->items[$k] = $v;
    }

    public function offsetExists($k)
    {
        return isset($this->items[$k]) || array_key_exists($k, $this->items);
    }

    public function offsetUnset($k)
    {
        unset($this->items[$k]);
    }

    public function offsetGet($k)
    {
        return isset($this->items[$k]) ? $this->items[$k] : null;
    }

    //implement Countable
    public function count()
    {
        return count($this->items);
    }

    //implement IteratorAggregate
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    //public interface to determine if container is empty
    public function isEmpty()
    {
        return empty($this->items);
    }

    //magic getter for data access
    public function __get($k)
    {
        return $this->offsetGet($k);
    }

/**
 * __isset
 * 
 * @note: since this is called for empty as well, so return not empty instead of isset
 */ 
    public function __isset($k)
    {
        return !empty($this->items[$k]);
    }
}
