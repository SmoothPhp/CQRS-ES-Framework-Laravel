<?php declare(strict_types=1);

namespace SmoothPhp\LaravelAdapter\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use SmoothPhp\Serialization\Exception\SerializedClassDoesNotExist;

/**
 * Class RebuildProjectionsCommand
 * @package SmoothPhp\LaravelAdapter\Console
 * @author Simon Bennett <simon@pixelatedcrow.com>
 */
final class RebuildProjectionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smoothphp:rebuild';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild all projection';

    /** @var Repository */
    private $config;

    /**
     * BuildLaravelEventStore constructor.
     * @param Repository $config
     */
    public function __construct(Repository $config)
    {
        parent::__construct();
        $this->config = $config;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach ($this->config->get('cqrses.pre_rebuild_commands') as $preRebuildCommand) {
            if (is_array($preRebuildCommand)) {
                $this->call(key($preRebuildCommand), current($preRebuildCommand));
            } else {
                $this->call($preRebuildCommand);
            }
        }

        $this->call(
            'smoothphp:project',
            ['projections' => implode(',', $this->config->get('cqrses.rebuild_projections'))]
        );

        foreach ($this->config->get('cqrses.post_rebuild_commands') as $postRebuildCommand) {
            if (is_array($postRebuildCommand)) {
                $this->call(key($postRebuildCommand), current($postRebuildCommand));
            } else {
                $this->call($postRebuildCommand);
            }
        }
    }
}
