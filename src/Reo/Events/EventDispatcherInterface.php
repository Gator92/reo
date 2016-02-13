<?php
namespace Reo\Events;

/**
 * Event Dispatcher Interface
 *
 * Extended from the Symfony interface. Adds a non-static method for getting subscribers
 * and an overloaded alias of the dispatch function, trigger.
 *
 * This file is part of the Reo Events Component.
 * Copyright (c) Schuyler W Langdon.
 *
 * For the full copyright and license information, please see the LICENSE
 * file that was distributed with this source code.
 */

interface EventDispatcherInterface extends \Symfony\Component\EventDispatcher\EventDispatcherInterface
{
    /**
     * Dispatches an event to all registered listeners.
     *
     * @param string $eventName The name of the event to dispatch. The name of
     *                          the event is the name of the method that is
     *                          invoked on listeners.
     * @param array | Event $event The event to pass to the event handlers/listeners.
     *                     If not supplied, an empty Event instance is created.
     *
     * @api
     */
    public function trigger($eventName, $event = null);

    /**
     * Adds an event subscriber. The subscriber is asked for all the events he is
     * interested in and added as a listener for these events.
     *
     * @param EventSubscriberInterface $subscriber The subscriber.
     *
     * @api
     */
    public function addSubscription(EventSubscriberInterface $subscriber);

    /**
     * Adds an event subscriber. The subscriber is asked for all the events he is
     * interested in and added as a listener for these events.
     *
     * @param EventSubscriberInterface $subscriber The subscriber.
     *
     * @api
     */
    public function removeSubscription(EventSubscriberInterface $subscriber);
}
