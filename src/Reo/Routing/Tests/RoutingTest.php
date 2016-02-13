<?php

use Reo\Http\Request;
use Reo\Routing\Route;
use Reo\Routing\RouteCollection;
use Reo\Routing\Router;

class RoutingRouteTest extends PHPUnit_Framework_TestCase
{
    public function testRouteMatching()
    {
        $router = new Router(new RouteCollection());
        //exact match
        $router->addRoute('hello-world', '/hello/world');
        //wildcard match
        $router->addRoute('hello-wild', '/hello/[name=%s]');
        //wildcard digit
        $router->addRoute('hello-page', '/world/[name=%s]/[page=%d]');
        //wildcard optional
        $router->addRoute('hello-blue', '/blue/[name=*s]');
        //multiple params
        $router->addRoute('hello-many', '/[name=%s]/[color=%s]/[page=%d]');

        //collection is set
        $this->assertEquals(['hello-world', 'hello-wild', 'hello-page', 'hello-blue', 'hello-many'], array_keys($router->getRouteCollection()->toArray()));

        //match exact
        $match = $router->match(Request::create('/hello/world', 'GET'));

        try {
            $this->assertInstanceOf('\Reo\Routing\Route', $match['route']);
        } catch (\Exception $e) {
            echo $e->getMessage(), "\n";
        }

        $this->assertEquals('hello-world', $match['name']);
        $this->assertEmpty($match['params']);

        //match wildcard
        $match = $router->match(Request::create('/hello/blue', 'GET'));

        try {
            $this->assertInstanceOf('\Reo\Routing\Route', $match['route']);
        } catch (\Exception $e) {
            echo $e->getMessage(), "\n";
        }

        $this->assertEquals('hello-wild', $match['name']);
        $this->assertEquals(['name' => 'blue'], $match['params']);

        //wildcard with number present
        $match = $router->match(Request::create('/world/any/9', 'GET'));

        try {
            $this->assertInstanceOf('\Reo\Routing\Route', $match['route']);
        } catch (\Exception $e) {
            echo $e->getMessage(), "\n";
        }

        $this->assertEquals('hello-page', $match['name']);
        $this->assertEquals(['name' => 'any', 'page' => '9'], $match['params']);

        //match wildcard with number not present
        $match = $router->match(Request::create('/world/any', 'GET'));

        try {
            $this->assertInstanceOf('\Reo\Routing\Route', $match['route']);
        } catch (\Exception $e) {
            echo $e->getMessage(), "\n";
        }

        $this->assertEquals('hello-page', $match['name']);
        $this->assertEquals(['name' => 'any'], $match['params']);

        //match required wildcard not present
        $match = $router->match(Request::create('/hello', 'GET'));
        $this->assertEquals(false, $match);

        //match optional wildcard present
        $match = $router->match(Request::create('/blue/yonder', 'GET'));

        try {
            $this->assertInstanceOf('\Reo\Routing\Route', $match['route']);
        } catch (\Exception $e) {
            echo $e->getMessage(), "\n";
        }

        $this->assertEquals('hello-blue', $match['name']);
        $this->assertEquals(['name' => 'yonder'], $match['params']);

        //match optional wildcard present
        $match = $router->match(Request::create('/blue', 'GET'));

        try {
            $this->assertInstanceOf('\Reo\Routing\Route', $match['route']);
        } catch (\Exception $e) {
            echo $e->getMessage(), "\n";
        }

        $this->assertEquals('hello-blue', $match['name']);
        $this->assertEmpty($match['params']);

         //match multiple
        $match = $router->match(Request::create('/schuyler/orange/8', 'GET'));

        try {
            $this->assertInstanceOf('\Reo\Routing\Route', $match['route']);
        } catch (\Exception $e) {
            echo $e->getMessage(), "\n";
        }

        $this->assertEquals('hello-many', $match['name']);
        $this->assertEquals(['name' => 'schuyler', 'color' => 'orange', 'page' => '8'], $match['params']);
    }

    public function testRouteContextMatching()
    {
        $router = new Router(new RouteCollection());
        $router->addRoute('hello-world', '/hello/world', null, array('method' => 'POST', 'protocol' => 'https'));
        //no POST
        $match = $router->match(Request::create('https://example.com/hello/world', 'GET'));
        $this->assertEquals(false, $match);
        //no HTTPS
        $match = $router->match(Request::create('/hello/world', 'POST'));
        $this->assertEquals(false, $match);
        //matching context
        $match = $router->match($request = Request::create('https://example.com/hello/world', 'POST'));
        try {
            $this->assertInstanceOf('\Reo\Routing\Route', $match['route']);
        } catch (\Exception $e) {
            echo $e->getMessage(), "\n";
        }
    }

    public function testCallback()
    {
        $router = new Router(new RouteCollection());
        $router->addRoute('hello-world', '/hello/[name=*s]', function ($args) {return 'hello '.$args['name'];}, null, array('name' => 'bob'));
        $match = $router->match(Request::create('/hello', 'GET'));
        $this->assertEquals('hello bob', $match['route']->__invoke());
        //name sub in callback
        $match = $router->match(Request::create('/hello/rik', 'GET'));
        $this->assertEquals('hello rik', $match['route']->__invoke($match['params']));
    }
}
