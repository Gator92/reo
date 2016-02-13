<?php

use Reo\DependencyInjection\ContainerEventAware;
use Reo\Events\EventDispatcher;

class ContainerEventAwareTest extends PHPUnit_Framework_TestCase
{
    public function testRegisterDispatcher()
    {
        //register object directly
        $container = new ContainerEventAware();
        $container->setEventDispatcher(new EventDispatcher());
        $this->assertInstanceOf('Reo\Events\EventDispatcher', $container->getEventDispatcher());
        
        //register from it's own service container
        $container->register('EventHandler', function () {return new EventDispatcher();});
        $container->setEventDispatcher('EventHandler');
        $this->assertInstanceOf('Reo\Events\EventDispatcher', $container->getEventDispatcher());

        //non-compatible handler
        $this->setExpectedException('InvalidArgumentException');
        $container->setEventDispatcher(new \StdClass());
    }

    public function testSubscribers()
    {
        $container = new ContainerEventAware();
        //writes the event callbacks
        $container->setParam('scribe', array());
        $container->register('scribe', function ($c, $id, $args) {
            $scribe = $c->getParam($id);
            $scribe[] = $args;
            $c->setParam($id, $scribe);
        }, false);
        //register event handler fNs
        $container->register('apples', function ($c) {$c->get('scribe', 'Apples');}, false);
        $container->register('oranges', function ($c) {$c->get('scribe', 'Oranges');}, false);
        $container->register('peaches', function ($c) {$c->get('scribe', 'Peaches');}, false);
        $container->register('swiss', function ($c) {return new Cheese('Swiss Alps');});
        //register events
        $events = array(
            'foo.before' => array(array('apples', false, 10), array('peaches', false, 10), array('oranges', false, 20)),
            'foo.after'  => 'apples'
        );
        $container->subscribe($events);
        $expected = array(
            'foo.before' => array(
                10 => array(array('apples', false), array('peaches', false)),
                20 => array(array('oranges', false))
            ),
            'foo.after'  => array(
                10 => array(array('apples', false))
            )
        );
        $this->assertSame($expected, $container->getSubscribed());
        //assign the dispatcher
        $container->setEventDispatcher(new EventDispatcher());
        //trigger an event
        $container->getEventDispatcher()->trigger('foo.before');
        $expected = array('Apples', 'Peaches', 'Oranges');
        $this->assertSame($expected, $container->getParam('scribe'));
    }
}
