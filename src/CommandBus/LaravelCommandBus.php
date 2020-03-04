<?php declare(strict_types=1);

namespace SmoothPhp\LaravelAdapter\CommandBus;

use SmoothPhp\Contracts\CommandBus\Command;
use SmoothPhp\Contracts\CommandBus\CommandBus;

/**
 * Class LaravelCommandBus
 * @package SmoothPhp\LaravelAdapter\CommandBus
 * @author Simon Bennett <simon@pixelatedcrow.com>
 */
final class LaravelCommandBus implements CommandBus
{
    public function __construct()
    {
    }

    /**
     * @param Command $command
     *
     * @return void
     * @throws \Exception
     */
    public function execute(Command $command)
    {
        throw new \Exception('Not implemented [execute] method');
    }
}
