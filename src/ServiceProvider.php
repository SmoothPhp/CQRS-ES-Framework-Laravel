<?php
namespace SmoothPhp\LaravelAdapter;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use SmoothPhp\CommandBus\SimpleCommandBus;
use SmoothPhp\CommandBus\SimpleCommandTranslator;
use SmoothPhp\Contracts\CommandBus\CommandBus;
use SmoothPhp\Contracts\EventBus\EventBus;
use SmoothPhp\Contracts\EventDispatcher\EventDispatcher;
use SmoothPhp\Contracts\EventStore\EventStore;
use SmoothPhp\Contracts\Serialization\Serializer;
use SmoothPhp\LaravelAdapter\CommandBus\LaravelCommandBusHandlerResolver;
use SmoothPhp\LaravelAdapter\Console\BuildLaravelEventStore;
use SmoothPhp\LaravelAdapter\Console\EventStoreBranchSwap;
use SmoothPhp\LaravelAdapter\Console\Generators\MakeAggregate;
use SmoothPhp\LaravelAdapter\Console\Generators\MakeCommand;
use SmoothPhp\LaravelAdapter\Console\Generators\MakeCommandHandler;
use SmoothPhp\LaravelAdapter\Console\Generators\MakeEvent;
use SmoothPhp\LaravelAdapter\Console\Generators\ScaffoldAggregateCommand;
use SmoothPhp\LaravelAdapter\Console\RebuildProjectionsCommand;
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

        $this->commands([
                            BuildLaravelEventStore::class,
                            RebuildProjectionsCommand::class,
                            ScaffoldAggregateCommand::class,
                            EventStoreBranchSwap::class,
                            MakeCommand::class,
                            MakeCommandHandler::class,
                            MakeAggregate::class,
                            MakeEvent::class,
                        ]);

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
            $this->app->singleton(CommandBus::class,
                function (Application $application) {
                    return new SimpleCommandBus(
                        new SimpleCommandTranslator(),
                        $application->make(LaravelCommandBusHandlerResolver::class));
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
            $app->bind(EventStore::class,
                function (Application $application) {
                    return new LaravelEventStore($application->make(DatabaseManager::class),
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
        $app->singleton(EventBus::class,
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
        $app->singleton(EventDispatcher::class,
            function (Application $application) {
                return $application->make($application['config']->get('cqrses.event_dispatcher'), [false]);
            }
        );
    }


}