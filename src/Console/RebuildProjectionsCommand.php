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
        $allEvents = $this->getAllEvents();
        $this->output->progressStart(count($allEvents));

        foreach ($allEvents as $eventRow) {
            $payload = json_decode($eventRow->payload, true)['payload'];
            $this->dispatcher->dispatch($eventRow->type,
                                        [
                                            (call_user_func(
                                                [
                                                    str_replace('.', '\\', $eventRow->type),
                                                    'deserialize'
                                                ],
                                                $payload
                                            ))
                                        ],
                                        true);


        }


        $this->output->progressFinish();
    }

    /**
     * @return \stdClass
     */
    private function getAllEvents()
    {
        return $this->databaseManager
            ->connection($this->config->get('cqrses.eventstore_connection'))
            ->table($this->config->get('cqrses.eventstore_table'))
            ->get();
    }
}
