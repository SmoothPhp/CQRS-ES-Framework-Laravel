<?php
namespace SmoothPhp\LaravelAdapter\StrongConsistency;

use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as Cache;
use SmoothPhp\LaravelAdapter\EventBus\QueueToEventDispatcher;
/**
 * Class QueueToEventDispatcherWithCommandId
 * @package SmoothPhp\LaravelAdapter\StrongConsistency
 * @author Simon Bennett <simon@bennett.im>
 */
final class QueueToEventDispatcherWithCommandId
{
    /** @var QueueToEventDispatcher */
    private $queueToEventDispatcher;

    /** @var Cache */
    private $cache;

    /**
     * QueueToEventDispatcherWithCommandId constructor.
     * @param QueueToEventDispatcher $queueToEventDispatcher
     * @param Cache $cache
     */
    public function __construct(QueueToEventDispatcher $queueToEventDispatcher, Cache $cache)
    {
        $this->queueToEventDispatcher = $queueToEventDispatcher;
        $this->cache = $cache;
    }

    /**
     * @param $job
     * @param $data
     * @return mixed
     */
    public function fire($job, $data)
    {
        $this->queueToEventDispatcher->fire($job, $data);
        $this->cache->add($data['command_id'], new Carbon(), 1);
    }
}
