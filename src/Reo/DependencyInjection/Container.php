<?php
namespace Reo\DependencyInjection;

/**
 * Basic Dependency Injection Container that contains services and
 * parameters separated into their own variable space.
 *
 * This file is part of the Reo Dependency Injection Component.
 * Copyright (c) Schuyler W Langdon.
 *
 * For the full copyright and license information, please see the LICENSE
 * file that was distributed with this source code.
 */

class Container
{
    /**
 * @param $items array Closure creator / Object stack
 * @note elements contain array [closure, shared flag, deps, class name]
 */
    protected $items;
/**
 * @param $params array Parameter stack
 */
    protected $params;

/**
 * Constructor
 *
 * Optionally inject contents upon instantiation.
 *
 * @param $items array to populate ctn
 * @param $params array to populate params
 */
    public function __construct(array $items = null, array $params = null)
    {
        $this->items = isset($items) ? $items : array();
        $this->params = isset($params) ? $params : array();
    }

    public static function create(array $items = null, array $params = null)
    {
        return new static($items, $params);//in the container aware the static subscribed may persist, which would mean something ugly like storing a persisance just to comply with the interface
    }

/**
 * Register a Service
 *
 * @param string $id the named index of the service/object
 * @param Closure $f the lambda
 * @param bool $shared flag to share service
 * @param array $deps an array of indexes to dependencies within the container
 * @param string $class optional class name only used for static calls
 */
    public function register($id, \Closure $f, $shared = true, array $deps = null, $class = null)
    {
        $this->items[$id] = array($f, $shared);
        if (isset($deps)) {
            //@note can be set with annotations, but not supported yet
            //@note helpful for compiling dependencies which is also not supported yet
            //can be empty which means no deps, null indicates not set
            $this->items[$id][3] = empty($deps) ? null : $deps;
        }
        if (isset($class)) {
            $this->items[$id][4] = $class;
        }
    }

    public function registerShared(array $services)
    {
        foreach ($services as $id => $closure) {
            if ($closure instanceof \Closure) {
                $this->items[$id] = array($closure, true);
            }
        }
    }

    public function get($id, $args = null)
    {
        if (!isset($this->items[$id])) {
            throw new \InvalidArgumentException(sprintf('The dependency you requested, [%s], does not exist', $id));
        }
        if ($this->items[$id][1]) {
            //shared
            if (empty($this->items[$id][2])) {
                $this->items[$id][2] = $this->items[$id][0]($this, $id, $args);
            }
            return $this->items[$id][2];
        }
        return $this->items[$id][0]($this, $id, $args);
    }

    public function __call($name, $args)
    {
        if (0 !== strpos($name, 'get') || '' === ($service = substr($name, 3)) || !isset($this->items[$service])) {
            throw new \BadMethodCallException('The Service you requested does not exist');
        }
        return $this->get($service, $args);
    }

/**
 * Standard getter/setter methods
*/

    public function has($id)
    {
        return isset($this->items[$id]);// || array_key_exists($id,$this-ctn) a service will not be null
    }

    public function setParam($id, $value)
    {
        $this->params[$id]=$value;
    }

    public function getParam($id)
    {
        return $this->hasParam($id) ? $this->params[$id] : null;
    }

    public function getParamKey($id, $key)
    {
        return $this->hasParamKey($id, $key) ? $this->params[$id][$key] : null;
    }

    public function hasParamKey($id, $key)
    {
        return isset($this->params[$id]) && (isset($this->params[$id][$key]) || array_key_exists($key, $this->params[$id]));// ? true : false;
    }

    public function hasParam($id)
    {
        return isset($this->params[$id]) || array_key_exists($id, $this->params);// ? true : false;
    }

    public function isEmptyParam($id)
    {
        return empty($this->params);
    }

    public function rmParam($id)
    {
        if ($this->hasParam($id)) {
            unset($this->params[$id]);
        }
    }

    public function is_strParameter($id)
    {
        return is_string($this->params[$id]);
    }

    public function getNew($id, array $args=null)
    {
        return isset($this->items[$id]) ? (isset($args) ? $this->items[$id][0]($this, $id, $args) : $this->items[$id][0]($this, $id)) : null;
    }

    public function getStatic($id, $method, $args = null)
    {
        if (!isset($this->items[$id])) {
            throw new \InvalidArgumentException(sprintf('The dependency you requested, [%s], does not exist', $id));
        }

        if (empty($this->items[$id][4])) {
            throw new \BadMethodCallException(sprintf('The class of [%s] is not defined, cannot call static method', $id));
        }

        $class = $this->items[$id][4];
        return $class::$method($args);
    }

    //set an object has to be shared because it can't be instantiated
    public function set($id, $obj)
    {
        if (!isset($obj) || !is_object($obj)) {
            throw new \InvalidArgumentException(sprintf('Cannot set [%s] to a parameter that is not of type object', $id));
        }
        $this->items[$id] = array(null, true, $obj);
    }

    public function hasInstance($id)
    {
        return isset($this->items[$id]) && isset($this->items[$id][2]);
    }

    public function remove($id)
    {
        if ($this->has($id)) {
            unset($this->items[$id]);
        }
    }

/**
 * replace
 *
 * Replaces item in container
 *
 * @note should not be used once an app is started
 *
 * @param $id string identifier of the current item
 * @param $repId string indentifer of the container item to use for replacement
 */
    public function replace($id, $repId)
    {
        if (isset($this->items[$repId])) {
            //isset($this->items[$id][2] return false
            $this->remove($id);
            $this->items[$id] = $this->items[$repId];
            unset($this->items[$repId]);
        }
        return false;
    }

    public function getClosure($id)
    {
        return isset($this->items[$id]) ? (isset($this->items[$id][0]) ? $this->items[$id][0] : null)  : null;
    }

    public function toArray()
    {
        return $this->params;
    }

    public function all()
    {
        return $this->toArray();
    }
}
