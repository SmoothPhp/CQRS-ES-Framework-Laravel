<?php declare (strict_types = 1);
namespace SmoothPhp\LaravelAdapter\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use SmoothPhp\Contracts\Domain\DomainMessage;
use SmoothPhp\Contracts\EventDispatcher\EventDispatcher;
use SmoothPhp\Contracts\EventDispatcher\Subscriber;
use SmoothPhp\Contracts\EventStore\EventStore;
use SmoothPhp\Contracts\Projections\ProjectionServiceProvider;

/**
 * Class RunProjectionCommand
 * @package SmoothPhp\LaravelAdapter\Console
 * @author Simon Bennett <simon@bennett.im>
 */
final class RunProjectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smoothphp:project {projections}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Projections';

    /** @var Repository */
    private $config;

    /** @var EventDispatcher */
    private $eventDispatcher;

    /** @var EventStore */
    private $eventStore;
    /** @var Application */
    private $application;

    /**
     * RunProjectionCommand constructor.
     * @param Repository $config
     * @param EventDispatcher $eventDispatcher
     * @param EventStore $eventStore
     * @param Application $application
     */
    public function __construct(
        Repository $config,
        EventDispatcher $eventDispatcher,
        EventStore $eventStore,
        Application $application
    ) {
        parent::__construct();
        $this->config = $config;
        $this->eventDispatcher = $eventDispatcher;
        $this->eventStore = $eventStore;
        $this->application = $application;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
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

        $this->replayEvents($projections, $events);

    }

    /**
     * @param ProjectionServiceProvider $projectionServiceProvider
     */
    public function downMigration(ProjectionServiceProvider $projectionServiceProvider)
    {
        $this->line($projectionServiceProvider->down());
    }

    /**
     * @param ProjectionServiceProvider $projectionServiceProvider
     */
    public function upMigration(ProjectionServiceProvider $projectionServiceProvider)
    {
        $this->line($projectionServiceProvider->up());
    }

    /**
     * @param Collection|Subscriber[]
     * @param string[] $events
     */
    protected function replayEvents($projections, $events)
    {
        $eventCount = $this->eventStore->getEventCountByTypes($events);
        $start = 0;
        $take = 1000;

        $this->output->progressStart($eventCount);
        $dispatcher = $this->buildAndRegisterDispatcher($projections);


        while ($start < $eventCount) {
            foreach ($this->eventStore->getEventsByType($events, $start, $take) as $eventRow) {
                $this->dispatchEvent($dispatcher, $eventRow);
            }
            $start += $take;
            $this->output->progressAdvance($take > $eventCount ? $eventCount : $take);
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
                $eventRow->getPayload()
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
        $dispatcher = $this->application->make($this->config->get('cqrses.event_dispatcher'));

        $projections->each(
            function ($projection) use ($dispatcher) {
                $dispatcher->addSubscriber($projection);
            }
        );

        return $dispatcher;
    }
}
