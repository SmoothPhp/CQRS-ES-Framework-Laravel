<?php
namespace SmoothPhp\LaravelAdapter\EventStore;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\QueryException;
use SmoothPhp\Contracts\Domain\DomainEventStream;
use SmoothPhp\Contracts\Domain\DomainMessage;
use SmoothPhp\Contracts\EventStore\DomainEventStreamInterface;
use SmoothPhp\Contracts\EventStore\EventStore;
use SmoothPhp\Contracts\Serialization\Serializer;
use SmoothPhp\Domain\DateTime;
use SmoothPhp\EventStore\DuplicateAggregatePlayhead;
use SmoothPhp\EventStore\EventStreamNotFound;

/**
 * Class LaravelEventStore
 * @package SmoothPhp\LaravelAdapter\EventStore
 * @author Simon Bennett <simon@pixelatedcrow.com>
 */
final class LaravelEventStore implements EventStore
{
    /** @var Serializer */
    private $serializer;

    /** @var string */
    private $eventStoreTableName;

    /** @var Connection */
    private $db;

    /**
     * @param DatabaseManager $databaseManager
     * @param Serializer $serializer
     * @param string $eventStoreConnectionName
     * @param string $eventStoreTableName
     */
    public function __construct(
        DatabaseManager $databaseManager,
        Serializer $serializer,
        $eventStoreConnectionName,
        $eventStoreTableName
    ) {
        $this->db = $databaseManager->connection($eventStoreConnectionName);
        $this->serializer = $serializer;
        $this->eventStoreTableName = $eventStoreTableName;
    }

    /**
     * @param string $id
     * @return DomainEventStream
     * @throws EventStreamNotFound
     */
    public function load($id)
    {
        $rows = $this->db->table($this->eventStoreTableName)
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
     * @param bool $ignorePlayhead
     * @throws \PDOException
     * @throws \SmoothPhp\EventStore\DuplicateAggregatePlayhead
     * @throws \Illuminate\Database\QueryException
     */
    public function append($id, DomainEventStream $eventStream, bool $ignorePlayhead = false)
    {
        $id = (string)$id; //Used to thrown errors if ID will not cast to string

        $this->db->reconnect();
        $this->db->beginTransaction();

        try {
            foreach ($eventStream as $domainMessage) {
                $this->insertEvent($this->domainMessageToArray($domainMessage), $ignorePlayhead);
            }

            $this->db->commit();
        } catch (QueryException $ex) {
            $this->db->rollBack();

            throw  $ex;
        }
    }

    /**
     * @param array $eventRow
     * @param bool $ignorePlayhead
     * @throws DuplicateAggregatePlayhead
     */
    private function insertEvent(array $eventRow, bool $ignorePlayhead)
    {
        try {
            $this->db->table($this->eventStoreTableName)->insert($eventRow);
        } catch (\PDOException $ex) {
            if ((string) $ex->getCode() === '23000') {
                if ($ignorePlayhead) {
                    $eventRow['playhead'] ++;
                    return $this->insertEvent($eventRow, true);
                }
                throw new DuplicateAggregatePlayhead($eventRow['uuid'], $eventRow['playhead']);
            }
            throw $ex;
        }
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
            $this->serializer->deserialize(json_decode($row->metadata, true)),
            $this->serializer->deserialize(json_decode($row->payload, true)),
            new DateTime($row->recorded_on)
        );
    }

    /**
     * @param string[] $eventTypes
     * @return int
     */
    public function getEventCountByTypes($eventTypes)
    {
        return $this->db->table($this->eventStoreTableName)
                        ->whereIn('type', $eventTypes)
                        ->count();
    }

    /**
     * @param string[] $eventTypes
     * @param int $skip
     * @param int $take
     * @return DomainEventStream
     */
    public function getEventsByType($eventTypes, $skip, $take)
    {
        $rows = $this->db->table($this->eventStoreTableName)
                         ->select(['uuid', 'playhead', 'metadata', 'payload', 'recorded_on'])
                         ->whereIn('type', $eventTypes)
                         ->skip($skip)
                         ->take($take)
                         ->orderBy('recorded_on', 'asc')
                         ->orderBy('playhead', 'asc')
                         ->get();
        $events = [];

        foreach ($rows as $row) {
            $events[] = $this->deserializeEvent($row);
        }

        return new \SmoothPhp\Domain\DomainEventStream($events);
    }

    /**
     * @param DomainMessage $domainMessage
     * @return array
     */
    private function domainMessageToArray(DomainMessage $domainMessage): array
    {
        return [
            'uuid'        => (string)$domainMessage->getId(),
            'playhead'    => $domainMessage->getPlayHead(),
            'metadata'    => json_encode($this->serializer->serialize($domainMessage->getMetadata())),
            'payload'     => json_encode($this->serializer->serialize($domainMessage->getPayload())),
            'recorded_on' => (string)$domainMessage->getRecordedOn(),
            'type'        => $domainMessage->getType(),
        ];
    }
}