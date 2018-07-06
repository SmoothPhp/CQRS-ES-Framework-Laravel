<?php declare(strict_types=1);

namespace SmoothPhp\LaravelAdapter\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use SmoothPhp\Contracts\Domain\DomainMessage;
use SmoothPhp\Contracts\EventDispatcher\EventDispatcher;
use SmoothPhp\Contracts\EventDispatcher\Subscriber;
use SmoothPhp\Contracts\EventStore\EventStore;
use SmoothPhp\Contracts\Projections\ProjectionServiceProvider;
use SmoothPhp\Domain\DomainEventStream;

/**
 * Class RunProjectionCommand
 * @package SmoothPhp\LaravelAdapter\Console
 * @author Simon Bennett <simon@pixelatedcrow.com>
 */
final class RunProjectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smoothphp:project {projections} {--transactions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Projections';

    /** @var Repository */
    private $config;

    /** @var EventStore */
    private $eventStore;
    /** @var Application */
    private $application;
    /** @var DatabaseManager */
    private $databaseManager;

    /**
     * RunProjectionCommand constructor.
     * @param Repository $config
     * @param EventStore $eventStore
     * @param Application $application
     * @param DatabaseManager $databaseManager
     */
    public function __construct(
        Repository $config,
        EventStore $eventStore,
        Application $application,
        DatabaseManager $databaseManager
    ) {
        parent::__construct();
        $this->config = $config;
        $this->eventStore = $eventStore;
        $this->application = $application;
        $this->databaseManager = $databaseManager;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle()
    {
        $projectionRequest = collect(explode(',', $this->argument('projections')));

        $projectionsServiceProviders = $projectionRequest->each(
            function ($projectionName) {
                if (!isset($this->config->get('cqrses.projections_service_providers')[$projectionName])) {
                    $this->error("{$projectionName} Does not exist, check cqrses config");

                    exit();
                }
            }
        )->map(
            function ($projectionName) {
                return $this->application->make(
                    $this->config->get('cqrses.projections_service_providers')[$projectionName]
                );
            }
        )->each(
            function (ProjectionServiceProvider $projectionClass) {
                $this->downMigration($projectionClass);
            }
        )->each(
            function (ProjectionServiceProvider $projectionClass) {
                $this->upMigration($projectionClass);
            }
        );

        /** @var Collection|Subscriber[] $projections */
        $projections = $projectionsServiceProviders->map(
            function (ProjectionServiceProvider $projectServiceProvider) {
                return collect($projectServiceProvider->getProjections())->map(
                    function ($projection) {
                        return $this->application->make($projection);
                    }
                );
            }
        )->collapse();

        $events = $projections->map(
            function (Subscriber $subscriber) {
                return array_keys($subscriber->getSubscribedEvents());
            }
        )->collapse()->map(
            function ($eventClassName) {
                return str_replace('\\', '.', $eventClassName);
            }
        );

        $this->replayEvents($projections, $events->toArray());
    }

    /**
     * @param ProjectionServiceProvider $projectionServiceProvider
     */
    public function downMigration(ProjectionServiceProvider $projectionServiceProvider)
    {
        $response = $projectionServiceProvider->down();
        $this->line($response ?? 'Migrated Down: ' . get_class($projectionServiceProvider));
    }

    /**
     * @param ProjectionServiceProvider $projectionServiceProvider
     */
    public function upMigration(ProjectionServiceProvider $projectionServiceProvider)
    {
        $response = $projectionServiceProvider->up();
        $this->line($response ?? 'Migrated Up: ' . get_class($projectionServiceProvider));
    }

    /**
     * @param Collection|Subscriber[]
     * @param string[] $events
     *
     * @throws \Exception
     */
    protected function replayEvents($projections, $events)
    {
        $eventCount = $this->eventStore->getEventCountByTypes($events);
        $take = (int)$this->config->get('cqrses.rebuild_transaction_size', 1000);

        $this->output->progressStart($eventCount);
        $dispatcher = $this->buildAndRegisterDispatcher($projections);

        /** @var DomainEventStream $eventStream */
        foreach ($this->eventStore->getEventsByType($events, $take) as $eventStream) {
            if ($this->option('transactions')) {
                $this->databaseManager->connection()->beginTransaction();
            }
            foreach ($eventStream as $eventRow) {
                $this->dispatchEvent($dispatcher, $eventRow);
            }
            if ($this->option('transactions')) {
                $this->databaseManager->connection()->commit();
            }
            $this->output->progressAdvance($take);
        }

        $this->output->progressFinish();
        $this->line((memory_get_peak_usage(true) / 1024 / 1024) . "mb Peak Usage", false);
    }

    /**
     * @param EventDispatcher $eventDispatcher
     * @param $eventRow
     */
    protected function dispatchEvent(EventDispatcher $eventDispatcher, DomainMessage $eventRow)
    {
        $eventDispatcher->dispatch(
            $eventRow->getType(),
            [
                $eventRow->getPayload(),
            ],
            true
        );
    }

    /**
     * @param Collection $projections
     * @return EventDispatcher
     */
    protected function buildAndRegisterDispatcher($projections)
    {
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->application->make(
            $this->config->get(
                'cqrses.rebuild_event_dispatcher',
                $this->config->get('cqrses.event_dispatcher')
            )
        );

        $projections->each(
            function ($projection) use ($dispatcher) {
                $dispatcher->addSubscriber($projection);
            }
        );

        return $dispatcher;
    }
}
