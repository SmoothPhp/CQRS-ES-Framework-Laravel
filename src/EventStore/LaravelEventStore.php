<?php
namespace SmoothPhp\LaravelAdapter\EventStore;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\QueryException;
use SmoothPhp\Contracts\Domain\DomainEventStream;
use SmoothPhp\Contracts\Domain\DomainMessage;
use SmoothPhp\Contracts\EventStore\DomainEventStreamInterface;
use SmoothPhp\Contracts\EventStore\EventStore;
use SmoothPhp\Contracts\EventStore\EventStreamNotFound;
use SmoothPhp\Contracts\Serialization\Serializer;
use SmoothPhp\Domain\DateTime;

/**
 * Class LaravelEventStore
 * @package SmoothPhp\LaravelAdapter\EventStore
 * @author Simon Bennett <simon@bennett.im>
 */
final class LaravelEventStore implements EventStore
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var SerializerInterface
     */
    private $payloadSerializer;

    /**
     * @var SerializerInterface
     */
    private $metadataSerializer;

    /**
     * @param DatabaseManager $databaseManager
     * @param Serializer $payloadSerializer
     * @param Serializer $metadataSerializer
     */
    public function __construct(
        DatabaseManager $databaseManager,
        Serializer $payloadSerializer,
        Serializer $metadataSerializer
    ) {
        $this->db = $databaseManager;
        $this->payloadSerializer = $payloadSerializer;
        $this->metadataSerializer = $metadataSerializer;
    }

    /**
     * @param string $id
     * @return DomainEventStream
     * @throws EventStreamNotFound
     */
    public function load($id)
    {
        $rows = $this->db->connection('eventstore')->table('eventstore')
                         ->select(['uuid', 'playhead', 'metadata', 'payload', 'recorded_on'])
                         ->where('uuid', $id)
                         ->orderBy('playhead', 'asc')
                         ->get();
        $events = [];

        foreach ($rows as $row) {
            $events[] = $this->deserializeEvent($row);
        }

        if (empty($events)) {
            throw new EventStreamNotFound(sprintf('EventStream not found for aggregate with id %s', $id));
        }

        return new \SmoothPhp\Domain\DomainEventStream($events);
    }

    /**
     * @param mixed $id
     * @param DomainEventStream $eventStream
     */
    public function append($id, DomainEventStream $eventStream)
    {
        $id = (string)$id; //Used to thrown errors if ID will not cast to string

        $this->db->beginTransaction();

        try {
            foreach ($eventStream as $domainMessage) {
                $this->insertEvent($this->db, $domainMessage);
            }

            $this->db->commit();
        } catch (QueryException $ex) {
            $this->db->rollBack();

            throw  $ex;
        }
    }

    /**
     * @param DatabaseManager $db
     * @param DomainMessage $domainMessage
     */
    private function insertEvent(DatabaseManager $db, DomainMessage $domainMessage)
    {

        $db->connection('eventstore')
           ->table('eventstore')
           ->insert(
               [
                   'uuid'        => (string)$domainMessage->getId(),
                   'playhead'    => $domainMessage->getPlayHead(),
                   'metadata'    => json_encode($this->metadataSerializer->serialize($domainMessage->getMetadata())),
                   'payload'     => json_encode($this->payloadSerializer->serialize($domainMessage->getPayload())),
                   'recorded_on' => (string)$domainMessage->getRecordedOn(),
                   'type'        => $domainMessage->getType(),
               ]
           );
    }

    /**
     * @param \stdClass
     * @return DomainMessage
     */
    private function deserializeEvent($row)
    {
        return new \SmoothPhp\Domain\DomainMessage(
            $row->uuid,
            $row->playhead,
            $this->metadataSerializer->deserialize(json_decode($row->metadata, true)),
            $this->payloadSerializer->deserialize(json_decode($row->payload, true)),
            new DateTime($row->recorded_on)
        );
    }
}