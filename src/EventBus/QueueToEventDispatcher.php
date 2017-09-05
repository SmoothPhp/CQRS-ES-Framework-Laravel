<?php declare(strict_types=1);

namespace SmoothPhp\LaravelAdapter\EventBus;

use SmoothPhp\Contracts\EventDispatcher\EventDispatcher;
use SmoothPhp\Contracts\Serialization\Serializer;

/**
 * Class QueueToEventDispatcher
 * @package SmoothPhp\LaravelAdapter\EventBus
 * @author Simon Bennett <simon@pixelatedcrow.com>
 */
final class QueueToEventDispatcher
{
    /** @var EventDispatcher */
    private $eventDispatcher;

    /** @var Serializer */
    private $serializer;

    /**
     * QueueToEventDispatcher constructor.
     * @param EventDispatcher $eventDispatcher
     * @param Serializer $serializer
     */
    public function __construct(EventDispatcher $eventDispatcher, Serializer $serializer)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
    }

    /**
     * @param $job
     * @param $data
     * @return mixed
     */
    public function fire($job, $data)
    {
        $payload = (json_decode($data['payload'],true));

        $event = call_user_func([
                                    str_replace('.', '\\', $data['type']),
                                    'deserialize'
                                ],
                                $payload['payload']);

        $this->eventDispatcher->dispatch($data['type'], [$event]);

        return $job->delete();
    }

}