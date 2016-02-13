<?php
/**
 * Dependency Injection Container that mediates events. Allows lazy loading for event listeners.
 *
 * This file is part of the Reo Dependency Injection Component.
 * Copyright (c) Schuyler W Langdon.
 *
 * For the full copyright and license information, please see the LICENSE
 * file that was distributed with this source code.
 *
 * @todo: Add option to compile deps.
 */
 
namespace Reo\DependencyInjection;

class ContainerEventAware extends Container implements \Reo\Events\EventSubscriberInterface
{
    protected $eventDispatcher;
    protected $subscribed = array();

    public function __construct(array $items = null, array $params = null)
    {
        parent::__construct($items, $params);
    }

    public function register($id, \Closure $f, $shared = true, array $deps = null, $class = null, array $events = null)
    {
        $this->items[$id] = array($f, $shared);
        if (isset($deps)) {
            //null indicates no dep key non-existent is not set
            $this->items[$id][3] = $deps;
        }
        if (isset($events)) {
            $this->subscribe(array_map(
                function ($map) use (&$id) {
                    array_unshift((array)$map, $id);
                    return $map;
                },
            $events));
        }
    }

    public function getSubscriptions()
    {
        $subscribed = array();
        foreach ($this->subscribed as $eventName => $priorityGroup) {
            foreach ($priorityGroup as $priority => $sub) {
                $subscribed[$eventName][] = array('onEventFired', $priority);
            }
        }
        return $subscribed;
    }

    public function getSubscribed()
    {
        return $this->subscribed;
    }

    public function listen($eventName, $dependencyName, $method = false, $priority = 10)
    {
        $this->subscribed[$eventName][$priority][] = array($dependencyName, $method);
    }
    
    public function subscribe(array $events)
    {
        //count($events) == count($events, COUNT_RECURSIVE)
        foreach ($events as $eventName => $map) {
            if (count($map) === count($map, COUNT_RECURSIVE)) {
                //allow a single array not to be wrapped
                $map = array($map);
            }
            foreach ($map as $depMap) {
                //array [dependencyName, method, priority]
                $depMap = (array)$depMap;
                $this->subscribed[$eventName][isset($depMap[2]) ? $depMap[2] : 10][] = array($depMap[0], isset($depMap[1]) ? $depMap[1] : false);
            }
        }
    }

    public function onEventFired(\Symfony\Component\EventDispatcher\Event $event, $priority)
    {
        if (!isset($this->subscribed[$eventName = $event->getName()][$priority])) {
            return;
        }

        foreach ($this->subscribed[$eventName][$priority] as $subscriber) {
            $service = $this->get($subscriber[0], $event);//call subscribers that are closures or load the service
            if (false !== ($method = $subscriber[1]) && null !== $service) {
                $service->{$method}($event);
            }
        }
    }

    public function getEventDispatcher()
    {
        if (isset($this->eventDispatcher)) {
            return $this->eventDispatcher;
        }
        //return null;
        throw new \RuntimeException(sprintf('There is no registered Event Dispatcher', $dispatcher));
    }

/**
 * setEventDispatcher
 *
 * @param $dispatcher string|EventDispatcherInterface
 */
    public function setEventDispatcher($dispatcher)
    {
        if (is_string($dispatcher)) {
            if (!isset($this->items[$dispatcher])) {
                throw new \InvalidArgumentException(sprintf('Dispatcher [%s] not registered', $dispatcher));
            } else {
                $dispatcher = $this->get($dispatcher);
            }
        }

        if ($dispatcher instanceof \Reo\Events\EventDispatcherInterface) {
            $this->eventDispatcher = $dispatcher;
            $this->eventDispatcher->addSubscription($this);
            return;
        }

        throw new \InvalidArgumentException(sprintf('Dispatcher is not of type EventDispatcherInterface', $dispatcher));
    }

/**
 * getSubscribedEvents
 *
 * Comply with the parent (Symfony) event subscriber interface although it has no purpose here.
 */
    public static function getSubscribedEvents()
    {
        return array();
    }
}
