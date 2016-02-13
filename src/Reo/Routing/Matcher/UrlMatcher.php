<?php
namespace Reo\Routing\Matcher;

/**
 * Url Matcher
 *
 * Matches routes to a url path. Also considers method and protocol.
 *
 * Copyright (c) Schuyler W Langdon.
 *
 * For the full copyright and license information, please see the LICENSE
 * file that was distributed with this source code.
 */

use Reo\Routing\RouteCollection;

class UrlMatcher
{
    private $routeCollection;
    private $mapper;
    private $params;
    private $context;

    public function __construct(RouteCollection $routeCollection, RouteMapper $mapper = null)
    {
        $this->routeCollection = $routeCollection;
        $this->mapper = isset($mapper) ? $mapper : new RouteMapper();
    }

    public function setContext(array $context)
    {
        //usage $matcher->setContext($request)->match($path)
        $this->context = $context;

        return $this;
    }

    public function match($path)
    {
        $this->params = array();
        //support contexts protocol and method
        if (!isset($this->context['protocol']) || !isset($this->context['method'])) {
            return false;
        }
        $ct = count($subPaths = $this->mapper->getSubPaths($path));
        $context = $this->context;
        //echo "path count\n";
        //var_dump($ct);
        $mapper = $this->mapper->clear();
        $routes = array_filter($this->routeCollection->toArray(), function ($route) use (&$ct, &$context, $mapper) {
            //var_dump($mapper->getSubPaths($route->getPath()), count($mapper->getSubPaths($route->getPath())));
            $subPaths = $mapper->getSubPaths($route->getPath());
            $minCount = $mapper->getMinCount($subPaths, $maxCount = count($subPaths));//no wonder regular expressions are popular
            return $route->getContext() === $context && $ct >= $minCount && $ct <= $maxCount;
        });
        if (empty($routes)) {
            return false;
        }
        foreach ($routes as $name => $route) {
            $this->params = array();//reset params
            $match = true;
            for ($routeMaps = $this->mapper->getRouteMaps($route, $name), $xx = 0; $xx < $ct; $xx++) {
                //fifo
                if (!$this->matchSubPaths($subPaths[$xx], $routeMaps[$xx], $name)) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                return array('name' => $name, 'route' => $route, 'params' => isset($this->params[$name]) ? $this->params[$name] : array());
            }
        }

        return false;
    }

    public function getParams()
    {
        return empty($this->params) ? false : $this->params;
    }

    public function getRoutes()
    {
        return $this->routeCollection;
    }

    protected function matchSubPaths($slug, RouteMap $routeMap, $routeName)
    {
        if (null === ($callback = $routeMap->getRule())) {
            //no callback, just identity
            if ($slug === $routeMap->getName()) {
                if (false !== ($param = $routeMap->getParam()) && null !== $param) {
                    $this->params[$routeName][$param] = $slug;
                }

                return true;
            }

            return false;
        }
        if (false !== $callback && !$callback($slug)) {
            //var_dump($slug, $callback($slug));
            return false;
        }
        $this->params[$routeName][$routeMap->getParam()] = $slug;

        return true;
    }
}
