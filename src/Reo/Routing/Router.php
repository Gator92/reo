<?php
namespace Reo\Routing;

/**
 * Router
 *
 * A wrapper for collecting routes and performing route matching.
 *
 * Copyright (c) Schuyler W Langdon.
 *
 * For the full copyright and license information, please see the LICENSE
 * file that was distributed with this source code.
 */

use Reo\Http\Request;

class Router
{
    protected $routeCollection;

    public function __construct(RouteCollection $routeCollection = null, $mapper = null)
    {
        //MapperInterface
        if (isset($routeCollection)) {
            $this->routeCollection = $routeCollection;
        }
        if (isset($mapper)) {
            $this->mapper = $mapper;
        }
    }

    public function addRoute($name, $path, callable $callback = null, array $context = null, array $args = null)
    {
        $this->routeCollection->add($name, new Route($path, $callback, $context, $args));
    }

    public function getRouteCollection()
    {
        return $this->routeCollection;
    }

    public function setRouteCollection(RouteCollection $routeCollection)
    {
        $this->routeCollection = $routeCollection;
    }

    public function loadMatcher($matcher)
    {
        $this->matcher = $matcher;

        return $this;
    }

    public function match($request)
    {
        if (!isset($this->matcher)) {
            $this->matcher = new Matcher\UrlMatcher($this->routeCollection);
        }

        if (!($request instanceof Request) && !($request instanceof \Symfony\Component\HttpFoundation\Request)) {
            throw new \InvalidArgumentException('Bad Request');
        }

        return $this->matcher->setContext(array('method' => $request->getMethod(), 'protocol' => $request->getScheme()))->match($request->getPathInfo());
    }
}
