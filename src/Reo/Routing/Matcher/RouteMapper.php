<?php
namespace Reo\Routing\Matcher;

/**
 * RouteMapper
 *
 * Maps route paths into a map of each subpath
 *
 * Copyright (c) Schuyler W Langdon.
 *
 * For the full copyright and license information, please see the LICENSE
 * file that was distributed with this source code.
 */

use Reo\Routing\Route;

class RouteMapper
{
    protected $routeMapCollection = array();

    public function getRouteMaps(Route $route, $name)
    {
        if (isset($this->routeMapCollection[$name])) {
            return $this->routeMapCollection;
        }

        return $this->routeMapCollection[$name] = array_map(function ($path) {
            if (strstr($path, '=%d]')) {
                $tmp = explode('=', $path);

                return new RouteMap(false, substr($tmp[0], 1), 'ctype_digit');
            }
            if (strstr($path, '=%s]') || strstr($path, '=*s]')) {
                $tmp = explode('=', $path);

                return new RouteMap(false, substr($tmp[0], 1), false);//will match anything
            } else {
                return new RouteMap($path);
            }
        }, $this->getSubPaths($route->getPath()));
    }

    public function getSubPaths($path)
    {
        $subPaths = array_values(
            array_filter(explode('/', $path), function ($subPath) {//@todo may cache the path somewhere
                return !empty($subPath);
            })
        );
        if (empty($subPaths)) {
            $subPaths = array('');
        }

        return $subPaths;
    }

    public function getMinCount($paths, $minCount)
    {
        //%d and *s are optionally matched
        if (1 === $minCount) {
            return $minCount;
        }
        $end = array_pop($paths);
        if (strstr($end, '=%d]') || strstr($end, '=*s]')) {
            return $this->getMinCount($paths, count($paths));
        }

        return $minCount;
    }

    public function clear()
    {
        $this->routeMapCollection = array();

        return $this;
    }
}
