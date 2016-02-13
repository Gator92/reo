<?php

use Reo\DependencyInjection\ContainerSharedTrait;
use Reo\DependencyInjection\Container;

class ContainerTest extends PHPUnit_Framework_TestCase
{
    public function testDicTrait()
    {
        $container = new TestContainer();

        $this->assertInstanceOf('\Traversable', $container);

        $container->register(['cheese' => function () {return 'gouda';}]);
        $this->assertEquals($container->get('cheese'), 'gouda');
        $this->assertEquals($container['cheese'], 'gouda');
        $this->assertEquals($container->cheese, 'gouda');
        $this->setExpectedException('InvalidArgumentException');
        $container->get('beer');
    }

    public function testShared()
    {
        $container = new Container();

        $container->register('Cheese', function ($c, $id) {
            return new Cheese('gouda', $id);
        });
        $instance = $container->get('Cheese');
        $this->assertInstanceOf('Cheese', $instance);
        //id is being passed
        $this->assertEquals('Cheese', $instance->id);
        $this->assertEquals('gouda', $instance->name);
        $cheese = new Cheese('gouda', 'Cheese');
        $this->assertEquals($instance, $cheese);
        $this->assertNotSame($instance, $cheese);
        $cheese = $container->get('Cheese');
        $this->assertSame($instance, $cheese);

        //gets deps from container
        $container->register('Whiz', function ($c, $id) {
            return new Whiz($c->get('Cheese'));
        });
        $this->assertSame($instance, $container->get('Whiz')->cheeser);

        //magic getter
        $cheese = $container->getCheese();
        $this->assertSame($instance, $cheese);

        //get new is not same shared instance
        $cheese = $container->getNew('Cheese');
        $this->assertNotSame($instance, $cheese);

        //get the closure
        $closure = $container->getClosure('Cheese');
        $this->assertInstanceOf('Closure', $closure);
        $this->assertEquals($instance, $closure('gouda', 'Cheese'));

        //non existent server throws exception
        $this->setExpectedException('InvalidArgumentException', sprintf('The dependency you requested, [%s], does not exist', 'Swiss'));
        $container->get('Swiss');
    }

    public function testNonShared()
    {
        $container = new Container();
        
        //register with shared flag false
        $container->register('Cheese', function ($c, $id) {
            return new Cheese('gouda', $id);
        }, false);
        $instance = $container->get('Cheese');
        $this->assertInstanceOf('Cheese', $instance);
        $cheese = $container->get('Cheese');
        $this->assertNotSame($instance, $cheese);
    }

    public function testPassArgs()
    {
        $container = new Container();
        $container->register($id = 'Gouda', function ($c, $id) {
            //arg is gotten from a container param, can be the same id
            return new Cheese($c->getParam($id), $id);
        });
        $container->setParam($id, $name = 'Goat');
        $instance = $container->get($id);
        $this->assertEquals($name, $instance->name);

        //external args
        $container->remove($id);
        $container->register($id = 'Gouda', function ($c, $id, $name) {
            //arg from ex
            return new Cheese($name, $id);
        });
        $instance = $container->get($id, $name = 'Swiss');
        $this->assertEquals($name, $instance->name);

        //will still be the initial arg for a shared instance
        $another = $container->get($id, $newName = 'Goat');
        $this->assertNotEquals($newName, $another->name);
        $this->assertEquals($name, $another->name);
    }

    public function testStatic()
    {
        $container = new Container();
        //these can be called directly, but sometimes makes mores sense to map from container
        $container->register('CheeseWhiz', function ($c, $id) {
            return new Whiz($c->get('Cheese'));
        }, true, null, 'Whiz');
        
        //verify method invoked
        $expected = Whiz::greet();
        $greeting = $container->getStatic('CheeseWhiz', 'greet');
        $this->assertEquals($expected, $greeting);
        
        //verify it's not instantiated
        $this->assertFalse($container->hasInstance('CheeseWhiz'));
        
        //pass args
        $expected = Whiz::greet($name = 'cheddar');
        $greeting = $container->getStatic('CheeseWhiz', 'greet', $name);
        $this->assertEquals($expected, $greeting);

        //throws exception when class is unknown
        $container->register('CheddarWhiz', function ($c, $id) {
            return new Whiz($c->get('Cheese'));
        });
        $this->setExpectedException('BadMethodCallException');
        $greeting = $container->getStatic('CheddarWhiz', 'greet');
    }
}

class TestContainer implements \ArrayAccess, \Countable, \IteratorAggregate
{
    use ContainerSharedTrait;
}

class Cheese
{
    public $id;
    public $name;

    public function __construct($name, $id)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function speak()
    {
        return 'Hello my name is ' . $name;
    }
}

class Whiz
{
    public $cheeser;
    public $dip;

    public function __construct(Cheese $cheese, $dip = null)
    {
        $this->cheeser = $cheese;
        if (isset($dip)) {
            $this->dip = $dip;
        }
    }

    public static function greet($name = null)
    {
        return 'Hello cheese' . (isset($name) ? ' ' . $name : '');
    }
}
