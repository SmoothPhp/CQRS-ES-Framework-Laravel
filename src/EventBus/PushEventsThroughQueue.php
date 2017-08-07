<?php
namespace SmoothPhp\LaravelAdapter\EventBus;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Queue\Queue;
use SmoothPhp\Contracts\Domain\DomainMessage;
use SmoothPhp\Contracts\EventBus\EventListener;
use SmoothPhp\Contracts\Serialization\Serializer;

/**
 * Class PushEventsThroughQueue
 * @package SmoothPhp\LaravelAdapter\EventBus
 * @author Simon Bennett <simon@pixelatedcrow.com>
 */
final class PushEventsThroughQueue implements EventListener
{
    /** @var Queue */
    private $queue;

    /** @var Serializer */
    private $serializer;
    /** @var Repository */
    private $config;

    /**
     * PushEventsThroughQueue constructor.
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
     * @param DomainMessage $domainMessage
     * @return void
     */
    public function handle(DomainMessage $domainMessage)
    {
        $this->queue->push(
            QueueToEventDispatcher::class,
            [
                'uuid'        => (string)$domainMessage->getId(),
                'playhead'    => $domainMessage->getPlayHead(),
                'metadata'    => json_encode($this->serializer->serialize($domainMessage->getMetadata())),
                'payload'     => json_encode($this->serializer->serialize($domainMessage->getPayload())),
                'recorded_on' => $domainMessage->getRecordedOn()->format('Y-m-d H:i:s'),
                'type'        => $domainMessage->getType(),
            ],
            $this->config->get('cqrses.queue_name', 'default')
        );
    }
}