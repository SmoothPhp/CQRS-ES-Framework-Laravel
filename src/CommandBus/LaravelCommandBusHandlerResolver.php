<?php declare(strict_types=1);

namespace SmoothPhp\LaravelAdapter\CommandBus;

use Illuminate\Contracts\Foundation\Application;
use SmoothPhp\Contracts\CommandBus\CommandHandlerResolver;

/**
 * Class LaravelCommandBusHandlerResolver
 * @package SmoothPhp\LaravelAdapter\CommandBus
 * @author Simon Bennett <simon@pixelatedcrow.com>
 */
final class LaravelCommandBusHandlerResolver implements CommandHandlerResolver
{
    /** @var Application */
    private $application;

    /**
     * LaravelCommandBusHandlerResolver constructor.
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * @param string $className The command handler ID
     * @return mixed                The command handler
     */
    public function make($className)
    {
        return $this->application->make($className);
    }
}