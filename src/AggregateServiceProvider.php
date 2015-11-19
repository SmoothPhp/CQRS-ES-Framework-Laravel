<?php
namespace SmoothPhp\LaravelAdapter;

use Illuminate\Support\ServiceProvider;
use SmoothPhp\Contracts\EventDispatcher\EventDispatcher;

/**
 * Class AggregateServiceProvider
 *
 * A helper class for registering events with ease handles the class building
 *
 * @package SmoothPhp\LaravelAdapter
 * @author Simon Bennett <simon@bennett.im>
 */
abstract class AggregateServiceProvider extends LaravelServiceProvider
{
    /**
     *
     */
    public function boot()
    {
        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $this->app->make(EventDispatcher::class);

        foreach ($this->getEventSubscribers() as $eventSubscriber) {
            $eventDispatcher->addSubscriber($this->app->make($eventSubscriber));
        }

    }

    /**
     * @return array
     */
    abstract public function getEventSubscribers();

}
