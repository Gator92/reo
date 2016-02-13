<?php
/**
 * EventDispatcher
 *
 * Handles Events.
 *
 * This file is part of the Reo Events Component.
 * Copyright (c) Schuyler W Langdon.
 *
 * For the full copyright and license information, please see the LICENSE
 * file that was distributed with this source code.
 *
 * @note Internally, empty priorities or chains are removed
 * @note The default priority is 10 with 1 being the highest priority
 */

namespace Reo\Events;

use \Symfony\Component\EventDispatcher\Event;

class EventDispatcher implements EventDispatcherInterface
{
    protected $listeners = array();
    protected $sorted = array();

    /**
     * @see EventDispatcherInterface::dispatch
     *
     * @api
     */
    public function dispatch($eventName, Event $event = null)
    {
        if (false === ($listeners = $this->getListeners($eventName))) {
            return;
        }

        if (null === $event) {
            $event = new Event();
        }

        $this->doDispatch($listeners, $eventName, $event);
    }

/**
 * trigger
 *
 * Alias of dispatch with @param $event overloaded to contain args
 *
 * @param $eventName string
 * @param $event Event|string
 */
    public function trigger($eventName, $event = null)
    {
        if (false === ($listeners = $this->getListeners($eventName))) {
            return;
        }

        if (null === $event || !($event instanceof Event)) {
            $event = new StandardEvent((array)$event);
        }

        return $this->doDispatch($listeners, $eventName, $event);
    }

    /**
     * @see EventDispatcherInterface::getListeners
     */
    public function getListeners($eventName = null)
    {
        if (null !== $eventName) {
            if (!isset($this->listeners[$eventName])) {
                return false;
            }

            if (empty($this->sorted[$eventName])) {
                $this->sortListeners($eventName);
            }
            return $this->listeners[$eventName];
        }

        foreach (array_keys($this->listeners) as $eventName) {
            if (empty($this->sorted[$eventName])) {
                $this->sortListeners($eventName);
            }
        }

        return $this->listeners;
    }

    /**
     * @see EventDispatcherInterface::hasListeners
     */
    public function hasListeners($eventName = null)
    {
        return false !== $this->getListeners($eventName);
    }

    /**
     * @see EventDispatcherInterface::addListener
     *
     * @api
     */
    public function addListener($eventName, $listener, $priority = 0)
    {
        if (0 == $priority) {
            //no 0 priority
            $priority = 10;
        }
        $this->listeners[$eventName][$priority][] = $listener;
        $this->sorted[$eventName] = false;
    }

    /**
     * @see EventDispatcherInterface::removeListener
     */
    public function removeListener($eventName, $listener)
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            if (false !== ($key = array_search($listener, $listeners))) {
                unset($this->listeners[$eventName][$priority][$key]);
            }
        }

        $this->listeners[$eventName] = array_filter($this->listeners[$eventName]);
        if (empty($this->listeners[$eventName])) {
            unset($this->listeners[$eventName]);
        }

        $this->sorted[$eventName] = false;
    }

    public function removeAllListeners($eventName, $priority = null)
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        if (null !== $priority) {
            if (!isset($this->listeners[$eventName][$prority])) {
                return;
            }
            unset($this->listeners[$eventName][$prority]);
            if (empty($this->listeners[$eventName])) {
                unset($this->listeners[$eventName]);
            }
        } else {
            unset($this->listeners[$eventName]);
        }

        $this->sorted[$eventName] = false;
    }

    /**
     * @see EventDispatcherInterface::addSubscriber
     *
     * @api
     */
    public function addSubscriber(\Symfony\Component\EventDispatcher\EventSubscriberInterface $subscriber)
    {
        foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
            if (is_string($params)) {
                $this->addListener($eventName, array($subscriber, $params));
            } elseif (is_string($params[0])) {
                $this->addListener($eventName, array($subscriber, $params[0]), $params[1]);
            } else {
                foreach ($params as $listener) {
                    $this->addListener($eventName, array($subscriber, $listener[0]), isset($listener[1]) ? $listener[1] : 0);
                }
            }
        }
    }

    public function addSubscription(EventSubscriberInterface $subscriber)
    {
        foreach ($subscriber->getSubscriptions() as $eventName => $subscription) {
            foreach ($subscription as $listener) {
                $this->addListener($eventName, array($subscriber, $listener[0]), isset($listener[1]) ? $listener[1] : 10);
            }
        }
    }

    /**
     * @see EventDispatcherInterface::removeSubscriber
     */
    public function removeSubscriber(\Symfony\Component\EventDispatcher\EventSubscriberInterface $subscriber)
    {
        foreach ($subscriber->getSubscribedEvents as $eventName => $params) {
            if (is_array($params) && is_array($params[0])) {
                foreach ($params as $listener) {
                    $this->removeListener($eventName, array($subscriber, $listener[0]));
                }
            } else {
                $this->removeListener($eventName, array($subscriber, is_string($params) ? $params : $params[0]));
            }
        }
    }

    public function removeSubscription(EventSubscriberInterface $subscriber)
    {
        foreach ($subscriber->getSubscribedEvents as $eventName => $subscription) {
            foreach ($params as $listener) {
                $this->removeListener($eventName, array($subscriber, $listener[0]));
            }
        }
    }

    /**
     * Triggers the listeners of an event.
     *
     * This method can be overridden to add functionality that is executed
     * for each listener.
     *
     * @param array[callback] $listeners The event listeners.
     * @param string $eventName The name of the event to dispatch.
     * @param Event $event The event object to pass to the event handlers/listeners.
     */
    protected function doDispatch($listeners, $eventName, Event $event)
    {
        if (isset($event->chainable) && $event->chainable) {
            $event->setDispatcher($this);
        }

        $event->setName($eventName);

        foreach ($listeners as $priority => $priorityGroup) {
            foreach ($priorityGroup as $listener) {
                //pass the priority in case it's mapped by the subscribing interface
                $result = $listener instanceof \Closure ? $listener($event, $priority) : call_user_func($listener, $event, $priority);//$eventName, $this not sure why symfony passes these since they're already contained within the event itself
                if ($event->isPropagationStopped()) {
                    return $result;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Sorts the internal list of listeners for the given event by priority.
     *
     * @param string $eventName The name of the event.
     * @note This works the opposite of the Symfony implementation priority > 0 and <=1 is the highest priority
     */
    protected function sortListeners($eventName)
    {
        $this->sorted[$eventName] = array();

        if (isset($this->listeners[$eventName])) {
            ksort($this->listeners[$eventName]);
            $this->sorted[$eventName] = true;
        }
    }
}
