<?php declare(strict_types = 1);
namespace SmoothPhp\LaravelAdapter\StrongConsistency;

use SmoothPhp\CommandBus\SimpleCommandBus;
use SmoothPhp\Contracts\CommandBus\Command;
use SmoothPhp\Contracts\CommandBus\CommandBus;

/**
 * Class CommandBus
 * @package SmoothPhp\LaravelAdapter\StrongConsistency
 * @author Simon Bennett <simon@bennett.im>
 */
final class StrongConsistencyCommandBus implements CommandBus
{
    /** @var SimpleCommandBus */
    private $commandBus;

    /** @var string */
    private $lastCommandId;

    /**
     * NotificationsCommandBus constructor.
     * @param SimpleCommandBus $commandBus
     */
    public function __construct(SimpleCommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * @param Command $command
     * @return void
     */
    public function execute(Command $command)
    {
        $this->lastCommandId = (string)$command;
        $this->commandBus->execute($command);
    }

    /**
     * @return string
     */
    public function getLastCommandId()
    {
        return $this->lastCommandId;
    }
}
