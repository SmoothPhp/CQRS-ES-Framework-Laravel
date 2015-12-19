<?php
namespace SmoothPhp\LaravelAdapter\Console;

use Illuminate\Config\Repository;
use Illuminate\Console\Command;

/**
 * Class EventStoreBranchSwap
 * @package SmoothPhp\LaravelAdapter\Console
 * @author Simon Bennett <simon@bennett.im>
 */
final class EventStoreBranchSwap extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smoothphp:swapbranch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /** @var Repository */
    private $config;

    /**
     * Create a new command instance.
     *
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
        // Get current branch name

        //swap the confection
        // fouse rebuild

        //save config
        $branch = $this->getGitBranch();


        $this->replaceEnvConfig($branch);

        if (!\Schema::connection($this->config->get('cqrses.eventstore_connection'))->hasTable($branch)) {
            $this->call('smoothphp:buildeventstore', ['--force' => true]);
        }
        $this->call('smoothphp:rebuild');

    }

    protected function getGitBranch()
    {
        $shellOutput = [];
        exec('git branch | ' . "grep ' * '", $shellOutput);
        foreach ($shellOutput as $line) {
            if (strpos($line, '* ') !== false) {
                return trim(strtolower(str_replace(['* ', '/'], ['', '-'], $line)));
            }
        }

        return null;
    }

    /**
     * @param $branch
     */
    protected function replaceEnvConfig($branch)
    {
        $envFilePath = base_path() . '/.env';

        $rebuildFunction = function ($data) use ($branch) {
            if (stristr($data, 'DB_TABLE_EVENTSTORE')) {
                return "DB_TABLE_EVENTSTORE={$branch}\n";
            }

            return $data;
        };

        $contentArray = array_map($rebuildFunction,file($envFilePath));

        file_put_contents($envFilePath, implode('',$contentArray));
    }
}
