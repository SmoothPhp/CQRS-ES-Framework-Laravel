<?php declare(strict_types = 1);
namespace SmoothPhp\LaravelAdapter\StrongConsistency;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use SmoothPhp\CommandBus\SimpleCommandBus;
use SmoothPhp\CommandBus\SimpleCommandTranslator;
use SmoothPhp\Contracts\CommandBus\CommandBus;
use SmoothPhp\Contracts\CommandBus\CommandTranslator;
use SmoothPhp\LaravelAdapter\CommandBus\LaravelCommandBusHandlerResolver;

/**
 * Class StrongConsistencyCommandBusServiceProvider
 * @package SmoothPhp\LaravelAdapter\StrongConsistency
 * @author Simon Bennett <simon@bennett.im>
 */
final class StrongConsistencyCommandBusServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(CommandTranslator::class, SimpleCommandTranslator::class);

        $this->app->singleton(
            CommandBus::class,
            function (Application $application) {
                $simpleCommandBus = new SimpleCommandBus(
                    $application->make(CommandTranslator::class),
                    $application->make(LaravelCommandBusHandlerResolver::class)
                );

                return new StrongConsistencyCommandBus($simpleCommandBus);
            }
        );
    }
}
