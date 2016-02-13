<?php
namespace Reo\Routing;

/**
 * Route Collection
 *
 * Container for Route Objects
 *
 * Copyright (c) Schuyler W Langdon.
 *
 * For the full copyright and license information, please see the LICENSE
 * file that was distributed with this source code.
 */

class RouteCollection
{
    private $routes = array();

    public function __construct(array $routes = null)
    {
        if (isset($routes)) {
            $this->addRoutesArray($routes);
        }
    }

    public function add($name, Route $route)
    {
        $this->routes[$name] = $route;
    }

    public function addRouteArray(array $routes)
    {
        foreach ($routes as $key => $route) {
            $this->addRoute($key, $route);
        }
        $this->routes = array_replace($this->routes, $routes);
    }

    public function toArray()
    {
        return $this->routes;
    }
}
