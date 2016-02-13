<?php
/**
 * Standard Event
 *
 * A standard event that can be used in many cases in place of a custom event.
 * Args can be passed from the Event Dispatcher using the trigger method.
 *
 * This file is part of the Reo Events Component.
 * Copyright (c) Schuyler W Langdon.
 *
 * For the full copyright and license information, please see the LICENSE
 * file that was distributed with this source code.
 */

namespace Reo\Events;

class StandardEvent extends \Symfony\Component\EventDispatcher\Event
{
    protected $args = array();

    public function __construct(array $args = null)
    {
        if (isset($args)) {
            $this->args = $args;
        }
    }
    
    public function __get($name)
    {
        return $this->get($name);
    }

    public function get($name)
    {
        return isset($this->args[$name]) ? $this->args[$name] : null;
    }

    public function __isset($name)
    {
        return isset($this->args[$name]);
    }

    public function has($name)
    {
        return $this->__isset($name);
    }

    public function toArray()
    {
        return $this->args;
    }
}
