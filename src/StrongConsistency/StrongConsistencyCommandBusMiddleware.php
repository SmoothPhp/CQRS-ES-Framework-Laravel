<?php
namespace SmoothPhp\LaravelAdapter\StrongConsistency;

use SmoothPhp\CommandBus\SimpleCommandBus;
use SmoothPhp\Contracts\CommandBus\Command;
use SmoothPhp\Contracts\CommandBus\CommandBusMiddleware;

/**
 * Class CommandBus
 * @package SmoothPhp\LaravelAdapter\StrongConsistency
 * @author Simon Bennett <simon@bennett.im>
 */
final class StrongConsistencyCommandBusMiddleware implements CommandBusMiddleware
{
    /** @var string */
    private $lastCommandId;

    /**
     * @param $command
     * @param callable $next
     * @return mixed
     */
    public function execute(Command $command, callable $next)
    {
        $this->lastCommandId = (string)$command;
        $next($command);
    }

    /**
     * @return string
     */
    public function getLastCommandId()
    {
        return $this->lastCommandId;
    }
}
