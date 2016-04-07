<?php
namespace SmoothPhp\LaravelAdapter\CommandBus;

use SmoothPhp\Contracts\CommandBus\Command;
use SmoothPhp\Contracts\CommandBus\CommandBus;

/**
 * Class LaravelCommandBus
 * @package SmoothPhp\LaravelAdapter\CommandBus
 * @author Simon Bennett <simon@bennett.im>
 */
final class LaravelCommandBus implements CommandBus
{
    public function __construct()
    {
    }
    /**
     * @param Command $command
     * @return void
     */
    public function execute(Command $command)
    {
        throw new \Exception('Not implemented [execute] method');
    }
}
