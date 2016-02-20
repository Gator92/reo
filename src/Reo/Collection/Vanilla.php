<?php
namespace Reo\Collection;

/**
 * Vanilla Collection
 *
 * A simple container, base class or skip and use the TraversableTrait in your collection-based class.
 *
 * This file is part of the Reo Collection Component.
 * Copyright (c) Schuyler W Langdon.
 *
 * For the full copyright and license information, please see the LICENSE
 * file that was distributed with this source code.
 */

class Vanilla implements \ArrayAccess, \Countable, \IteratorAggregate
{
/**
 * Traversable Trait implements ArrayAccess, Countable, IteratorAggregate
 */
    use TraversableTrait;

/**
 * Constructor of class Vanilla Collection
 *
 * @return void
 */
    public function __construct(array $items = null)
    {
        if (isset($items)) {
            $this->items = $items;
        }
    }
}
