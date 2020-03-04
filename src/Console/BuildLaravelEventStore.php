<?php declare(strict_types=1);

namespace SmoothPhp\LaravelAdapter\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class BuildLaravelEventStore
 * @package SmoothPhp\LaravelAdapter\Console
 * @author Simon Bennett <simon@pixelatedcrow.com>
 */
final class BuildLaravelEventStore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smoothphp:buildeventstore {--force=false}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build the Laravel Event Store';

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
        if ($this->option('force') == 'true' || $this->confirm(
                "Are you sure you want to make a new table '{$this->config->get('cqrses.eventstore_table')}'"
                . " on connection '{$this->config->get('cqrses.eventstore_connection')}'"
                . " Do you wish to continue?"
            )
        ) {
            try {
                return $this->buildEventStoreTable();
            } catch (QueryException $ex) {
                $this->error("Error creating table :'{$this->config->get('cqrses.eventstore_table')}'");
                $this->error($ex->getMessage());
            }
        }
        $this->line("Stopping");
    }

    /**
     * Build eventstore table
     */
    protected function buildEventStoreTable()
    {
        Schema::connection($this->config->get('cqrses.eventstore_connection'))
              ->create(
                  $this->config->get('cqrses.eventstore_table'),
                  function (Blueprint $table) {
                      $table->increments('id');
                      $table->string('uuid', 56);
                      $table->integer('playhead')->unsigned();
                      $table->text('metadata');
                      $table->longText('payload');
                      $table->timestamp('recorded_on')->nullable()->index();
                      $table->string('type', 255)->index();
                      $table->unique(['uuid', 'playhead']);

                      $table->index(['id', 'type']);
                  }
              );
    }
}
