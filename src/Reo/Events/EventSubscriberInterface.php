<?php
namespace Reo\Events;

/**
 * Event Subscriber Interface
 *
 * Extends the Symfony interface to add a non-static method for getting subscribers.
 *
 * This file is part of the Reo Events Component.
 * Copyright (c) Schuyler W Langdon.
 *
 * For the full copyright and license information, please see the LICENSE
 * file that was distributed with this source code.
 */

interface EventSubscriberInterface extends \Symfony\Component\EventDispatcher\EventSubscriberInterface
{
    /**
 * getSubscriptions
 *
 * A non-static version of getSubscribers method.
 */
    public function getSubscriptions();
}
