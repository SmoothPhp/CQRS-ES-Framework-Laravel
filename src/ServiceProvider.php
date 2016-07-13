<?php
namespace SmoothPhp\LaravelAdapter;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use SmoothPhp\CommandBus\CommandHandlerMiddleWare;
use SmoothPhp\CommandBus\SimpleCommandBus;
use SmoothPhp\CommandBus\SimpleCommandTranslator;
use SmoothPhp\Contracts\CommandBus\CommandBus;
use SmoothPhp\Contracts\EventBus\EventBus;
use SmoothPhp\Contracts\EventDispatcher\EventDispatcher;
use SmoothPhp\Contracts\EventStore\EventStore;
use SmoothPhp\Contracts\Projections\ProjectionServiceProvider;
use SmoothPhp\Contracts\Serialization\Serializer;
use SmoothPhp\LaravelAdapter\CommandBus\LaravelCommandBusHandlerResolver;
use SmoothPhp\LaravelAdapter\Console\BuildLaravelEventStore;
use SmoothPhp\LaravelAdapter\Console\EventStoreBranchSwap;
use SmoothPhp\LaravelAdapter\Console\ExportEventStore;
use SmoothPhp\LaravelAdapter\Console\ImportEventStore;
use SmoothPhp\LaravelAdapter\Console\RebuildProjectionsCommand;
use SmoothPhp\LaravelAdapter\Console\RunProjectionCommand;
use SmoothPhp\LaravelAdapter\EventStore\LaravelEventStore;

/**
 * Class ServiceProvider
 * @package SmoothPhp\LaravelAdapter
 * @author Simon Bennett <simon@bennett.im>
 */
final class ServiceProvider extends \Illuminate\Support\ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

        $configPath = __DIR__ . '/../config/cqrses.php';
        $this->mergeConfigFrom($configPath, 'cqrses');

        $app = $this->app;

        $this->registerCommandBus($app);

        $this->registerSerializer($app);
        $this->registerEventStore($app);

        $this->registerEventDispatcher($app);
        $this->registerEventBus($app);

        $this->commands(
            [
                BuildLaravelEventStore::class,
                RebuildProjectionsCommand::class,
                EventStoreBranchSwap::class,
                ExportEventStore::class,
                ImportEventStore::class,
                RunProjectionCommand::class,
            ]
        );

    }

    public function boot()
    {
        $configPath = __DIR__ . '/../config/cqrses.php';
        $this->publishes([$configPath => $this->getConfigPath()], 'config');

    }

    /**
     * Get the config path
     *
     * @return string
     */
    protected function getConfigPath()
    {
        return config_path('cqrses.php');
    }

    /**
     * Publish the config file
     *
     * @param  string $configPath
     */
    protected function publishConfig($configPath)
    {
        $this->publishes([$configPath => config_path('cqrses.php')], 'config');
    }

    /**
     * @param Application $app
     */
    protected function registerCommandBus(Application $app)
    {
        if ($app['config']->get('cqrses.command_bus_enabled')) {
            $middlewareChain = [];

            foreach ($app['config']->get('cqrses.command_bus_middleware') as $middleware) {
                $app->singleton($middleware);
                $middlewareChain[] = $app->make($middleware);
            }

            $middlewareChain[] = new CommandHandlerMiddleWare(
                new SimpleCommandTranslator(),
                $app->make(LaravelCommandBusHandlerResolver::class)
            );


            $this->app->singleton(
                CommandBus::class,
                function () use ($middlewareChain) {
                    return new \SmoothPhp\CommandBus\CommandBus($middlewareChain);
                }
            );
        }
    }

    /**
     * @param Application $app
     */
    protected function registerSerializer(Application $app)
    {
        $app->bind(Serializer::class, $app['config']->get('cqrses.serializer'));
    }

    /**
     * @param Application $app
     */
    protected function registerEventStore(Application $app)
    {
        if ($app['config']->get('cqrses.laravel_eventstore_enabled')) {
            $app->bind(
                EventStore::class,
                function (Application $application) {
                    return new LaravelEventStore(
                        $application->make(DatabaseManager::class),
                        $application->make(Serializer::class),
                        $application['config']->get('cqrses.eventstore_connection'),
                        $application['config']->get('cqrses.eventstore_table')
                    );
                }
            );
        }
    }

    /**
     * @param Application $app
     */
    protected function registerEventBus(Application $app)
    {
        $app->singleton(
            EventBus::class,
            function (Application $application) {
                $eventBus = $application->make($application['config']->get('cqrses.event_bus'));

                foreach ($application['config']->get('cqrses.event_bus_listeners') as $listener) {
                    $eventBus->subscribe($application->make($listener));
                }

                return $eventBus;
            }
        );
    }

    /**
     * @param $app
     */
    protected function registerEventDispatcher(Application $app)
    {
        $app->singleton(
            EventDispatcher::class,
            function (Application $application) {
                /** @var EventDispatcher $dispatcher */
                $dispatcher = $application->make($application['config']->get('cqrses.event_dispatcher'));

                foreach ($this->getProjectionEventSubscribers($application) as $subscriber) {
                    $dispatcher->addSubscriber($subscriber);
                }

                return $dispatcher;
            }
        );
    }

    /**
     * @param Application $app
     * @return \SmoothPhp\Contracts\EventDispatcher\Subscriber[]|Collection
     */
    protected function getProjectionEventSubscribers(Application $app)
    {
        return collect($app['config']->get('cqrses.projections_service_providers'))->map(
            function ($projectionsServiceProvider) use ($app) {
                return $app->make($projectionsServiceProvider);
            }
        )->map(
            function (ProjectionServiceProvider $projectServiceProvider) use ($app) {
                return collect($projectServiceProvider->getProjections())->map(
                    function ($projection) use ($app) {
                        return $app->make($projection);
                    }
                );
            }
        )->collapse();
    }

}