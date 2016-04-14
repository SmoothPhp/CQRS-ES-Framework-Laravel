<?php
namespace SmoothPhp\LaravelAdapter\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use SmoothPhp\Contracts\EventDispatcher\EventDispatcher;
use SmoothPhp\Contracts\Serialization\Serializer;
use SmoothPhp\Serialization\Exception\SerializedClassDoesNotExist;

/**
 * Class RebuildProjectionsCommand
 * @package SmoothPhp\LaravelAdapter\Console
 * @author Simon Bennett <simon@bennett.im>
 */
final class RebuildProjectionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smoothphp:rebuild';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild all projection';

    /** @var Repository */
    private $config;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /** @var DatabaseManager */
    private $databaseManager;

    /** @var Serializer */
    private $serializer;


    /**
     * BuildLaravelEventStore constructor.
     * @param Repository $config
     * @param Application $application
     * @param DatabaseManager $databaseManager
     * @param Serializer $serializer
     */
    public function __construct(
        Repository $config,
        Application $application,
        DatabaseManager $databaseManager,
        Serializer $serializer
    ) {
        parent::__construct();
        $this->config = $config;

        $this->dispatcher = $application->make(EventDispatcher::class);
        $this->databaseManager = $databaseManager;
        $this->serializer = $serializer;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach ($this->config->get('cqrses.pre_rebuild_commands') as $preRebuildCommand) {
            if (is_array($preRebuildCommand)) {
                $this->call(key($preRebuildCommand), current($preRebuildCommand));
            } else {
                $this->call($preRebuildCommand);
            }
        }

        $this->replayEvents();

        foreach ($this->config->get('cqrses.post_rebuild_commands') as $postRebuildCommand) {
            if (is_array($postRebuildCommand)) {
                $this->call(key($postRebuildCommand), current($postRebuildCommand));
            } else {
                $this->call($postRebuildCommand);
            }
        }
    }

    /**
     *
     */
    protected function replayEvents()
    {
        $eventCount = $this->getEventCount();
        $start = 0;
        $take = 1000;
        $this->output->progressStart($eventCount);

        while ($start < $eventCount) {
            $this->databaseManager->connection(config('database.default'))->beginTransaction();
            foreach ($this->getFromEventStore($start, $take) as $eventRow) {
                $this->dispatchEvent($eventRow);
            }
            $this->databaseManager->connection(config('database.default'))->commit();

            $start += $take;
            $this->output->progressAdvance($take);
        }
        $this->output->progressFinish();

        $this->output->write((memory_get_peak_usage(true) / 1024 / 1024) . " MiB", false);
    }

    /**
     * @param $start
     * @param $take
     * @return \stdClass
     */
    private function getFromEventStore($start, $take)
    {
        return $this->databaseManager
            ->connection($this->config->get('cqrses.eventstore_connection'))
            ->table($this->config->get('cqrses.eventstore_table'))
            ->take($take)
            ->skip($start)
            ->get();
    }

    /**
     * @return int
     */
    private function getEventCount()
    {
        return $this->databaseManager
            ->connection($this->config->get('cqrses.eventstore_connection'))
            ->table($this->config->get('cqrses.eventstore_table'))
            ->count();
    }

    /**
     * @param $eventRow
     */
    protected function dispatchEvent($eventRow)
    {
        $this->dispatcher->dispatch(
            $eventRow->type,
            [
                (call_user_func(
                    [
                        str_replace('.', '\\', $eventRow->type),
                        'deserialize'
                    ],
                    json_decode($eventRow->payload, true)['payload']
                ))
            ],
            true
        );
    }
}
