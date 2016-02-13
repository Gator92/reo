<?php
namespace Reo\Routing\Matcher;

/**
 * Route Map
 *
 * Model for a the sub path of a Route path
 *
 * Copyright (c) Schuyler W Langdon.
 *
 * For the full copyright and license information, please see the LICENSE
 * file that was distributed with this source code.
 */

class RouteMap
{
    private $name;//the path component slug
    private $param;
    private $callback;

    public function __construct($name, $param = false, $callback = null)
    {
        if (false === ($this->name = $name)
          && (false === ($this->param = $param) || null === ($this->callback = $callback))) {
            throw new \InvalidArgumentException('UnNamed Routes must specify a callback');
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function getRule()
    {
        return $this->callback;
    }

    public function getParam()
    {
        return $this->param;
    }
}
