<?php
namespace Reo\Routing;

/**
 * Route
 *
 * Route Model
 *
 * Copyright (c) Schuyler W Langdon.
 *
 * For the full copyright and license information, please see the LICENSE
 * file that was distributed with this source code.
 * 
 * @note: Using the default 90/10 Reo Route Mappers, the 3 basic matching rules are as follows
 * 1) required named param: /hello/[name=%s]
 * 2) optional named param: /hello/[name=*s]
 * 3) named param with an option page: /hello/[name=%s]/[page=%d]
 */

class Route
{
    protected $path;
    protected $context = array('method' => 'GET', 'protocol' => 'http');
    protected $args = null;
    private $callback = null;

/**
 * Constructor
 * 
 * @param $path - string the matchable path
 * @param $callback callable optional, can be called upon matching if implemented
 * @param $context array [method, protocol]
 * @param $args mixed optional args to send to callback
 */
    public function __construct($path, callable $callback = null, array $context = null, array $args = null)
    {
        if (!isset($path)) {
            throw new InvalidArgumentException('A route must specify a path');
        }
        $this->path = $path;
        if (isset($callback)) {
            $this->callback = $callback;
        }
        if (isset($args)) {
            $this->args = $args;
        }
        if (isset($context) && $context !== $this->context) {
            $this->context = array_replace($this->context, array_intersect_key($context, $this->context));//only keys already in context
            //$this->context('method') = strtoupper($this->context('method'));
        }
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function hasCallback()
    {
        return isset($this->callback) && is_callable($this->callback);
    }

    public function doCallback(array $args = null)
    {
        if (!isset($this->callback)) {
            return null;
        }
        if (isset($args)) {
            $this->args = isset($this->args) ? array_replace($this->args, $args) : $args;
        }
        if ($this->callback instanceof \Closure) {
            return $this->callback->__invoke($this->args);
        }
        return call_user_func_array($this->callback, $this->args);
    }

    public function __invoke(array $args = null)
    {
        return $this->hasCallback() ? $this->doCallback($args) : null;
    }
}
