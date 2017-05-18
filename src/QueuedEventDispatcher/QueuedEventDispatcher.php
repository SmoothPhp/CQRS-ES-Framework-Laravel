<?php declare (strict_types=1);

namespace SmoothPhp\LaravelAdapter\QueuedEventDispatcher;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Queue\Queue;
use SmoothPhp\Contracts\EventDispatcher\EventDispatcher;
use SmoothPhp\Contracts\EventDispatcher\Subscriber;
use SmoothPhp\Contracts\Serialization\Serializer;

/**
 * Class QueuedEventDispatcher
 * @package SmoothPhp\LaravelAdapter\QueuedEventDispatcher
 * @author Simon Bennett <simon@bennett.im>
 */
final class QueuedEventDispatcher implements EventDispatcher
{
    /**
     * @var array
     */
    private $listeners = [];

    /**
     * @var array
     */
    private $sorted = [];
    /** @var Queue */
    private $queue;
    /** @var Serializer */
    private $serializer;
    /** @var Repository */
    private $config;

    /**
     * QueuedEventDispatcher constructor.
     * @param Queue $queue
     * @param Serializer $serializer
     * @param Repository $config
     */
    public function __construct(Queue $queue, Serializer $serializer, Repository $config)
    {
        $this->queue = $queue;
        $this->serializer = $serializer;
        $this->config = $config;
    }

    /**
     * @param string $eventName
     * @param array $arguments
     * @param bool $runProjectionsOnly
     */
    public function dispatch($eventName, array $arguments, $runProjectionsOnly = false)
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }
        $listeners = $this->getListenersInOrder($eventName);

        if (count($listeners) === 1) {
            call_user_func_array($listeners[0], $arguments);

            return;
        }

        foreach ($this->getListenersInOrder($eventName) as $listener) {
            $this->queue->push(
                QueuedEventHandler::class,
                [
                    'listener_class' => get_class($listener[0]),
                    'listener_method'         => $listener[1],
                    'event'      => $this->serializer->serialize($arguments[0])
                ],
                $this->config->get('cqrses.queue_name_handler', 'default')
            );
        }
    }

    /**
     * @param string $eventName
     * @param callable $callable
     * @param int $priority
     */
    public function addListener($eventName, callable $callable, $priority = 0)
    {
        $dotEventName = str_replace('\\', '.', $eventName);
        $this->listeners[$dotEventName][$priority][] = $callable;
        unset($this->sorted[$dotEventName]);

    }


    /**
     * @param Subscriber $subscriber
     * @return void
     */
    public function addSubscriber(Subscriber $subscriber)
    {
        foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
            if (is_string($params)) {
                $this->addListener($eventName, [$subscriber, $params]);
            } elseif (is_string($params[0])) {
                $this->addListener($eventName, [$subscriber, $params[0]], isset($params[1]) ? $params[1] : 0);
            } else {
                foreach ($params as $listener) {
                    $this->addListener($eventName, [$subscriber, $listener[0]], isset($listener[1]) ? $listener[1] : 0);
                }
            }
        }
    }

    /**
     * @param $eventName
     * @return array
     */
    protected function getListenersInOrder($eventName)
    {
        if (!isset($this->listeners[$eventName])) {
            return [];
        }
        if (!isset($this->sorted[$eventName])) {
            $this->sortListeners($eventName);
        }

        return $this->sorted[$eventName];
    }

    /**
     * Sorts the internal list of listeners for the given event by priority.
     *
     * @param string $eventName The name of the event.
     */
    private function sortListeners($eventName)
    {
        $this->sorted[$eventName] = [];

        krsort($this->listeners[$eventName]);
        $this->sorted[$eventName] = call_user_func_array('array_merge', $this->listeners[$eventName]);
    }
}
