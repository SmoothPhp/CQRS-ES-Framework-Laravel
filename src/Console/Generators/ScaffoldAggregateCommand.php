<?php
namespace SmoothPhp\LaravelAdapter\Console\Generators;

use Illuminate\Console\Command;

/**
 * Class ScaffoldAggregateCommand
 * @package SmoothPhp\LaravelAdapter\Console
 * @author Simon Bennett <simon@bennett.im>
 */
final class ScaffoldAggregateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smoothphp:scaffold {name} {namespace} {path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffolding Aggregate';

    public function handle()
    {
        $name = $this->argument('name');
        $path = base_path() . '/' . $this->argument('path');

        $folders = [
            'Commands',
            'Events',
            'Exceptions',
            'Listeners',
            'ValueObjects'
        ];

        foreach ($folders as $folder) {
            mkdir($path . '/' . $name . '/' . $folder, 0777, true);
        }
    }
}