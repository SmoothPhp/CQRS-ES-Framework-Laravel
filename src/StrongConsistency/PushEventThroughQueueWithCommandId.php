<?php declare(strict_types = 1);
namespace SmoothPhp\LaravelAdapter\StrongConsistency;

use FlixPremiere\Laravel\CommandNotfication\CommandBus\NotificationsCommandBus;
use Illuminate\Contracts\Queue\Queue;
use SmoothPhp\Contracts\CommandBus\CommandBus;
use SmoothPhp\Contracts\Domain\DomainMessage;
use SmoothPhp\Contracts\EventBus\EventListener;
use SmoothPhp\Contracts\Serialization\Serializer;

/**
 * Class PushEventThroughQueueWithCommandId
 * @package SmoothPhp\LaravelAdapter\StrongConsistency
 * @author Simon Bennett <simon@bennett.im>
 */
final class PushEventThroughQueueWithCommandId implements EventListener
{
    /** @var Queue */
    private $queue;

    /** @var Serializer */
    private $serializer;

    /** @var NotificationsCommandBus */
    private $notificationsCommandBus;

    /**
     * PushEventsThroughQueue constructor.
     * @param Queue $queue
     * @param Serializer $serializer
     * @param StrongConsistencyCommandBus|CommandBus $notificationsCommandBus
     */
    public function __construct(Queue $queue, Serializer $serializer, CommandBus $notificationsCommandBus)
    {
        $this->queue = $queue;
        $this->serializer = $serializer;
        $this->notificationsCommandBus = $notificationsCommandBus;
    }

    /**
     * @param DomainMessage $domainMessage
     * @return void
     */
    public function handle(DomainMessage $domainMessage)
    {
        $this->queue->push(
            QueueToEventDispatcherWithCommandId::class,
            [
                'uuid'        => (string)$domainMessage->getId(),
                'playhead'    => $domainMessage->getPlayHead(),
                'metadata'    => json_encode($this->serializer->serialize($domainMessage->getMetadata())),
                'payload'     => json_encode($this->serializer->serialize($domainMessage->getPayload())),
                'recorded_on' => (string)$domainMessage->getRecordedOn(),
                'type'        => $domainMessage->getType(),
                'command_id'  => $this->notificationsCommandBus->getLastCommandId(),
            ]
        );
    }
}
