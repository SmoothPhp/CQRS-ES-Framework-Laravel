<?php declare(strict_types=1);

namespace SmoothPhp\LaravelAdapter\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\DatabaseManager;

/**
 * Class ExportEventStore
 * @package SmoothPhp\LaravelAdapter\Console
 * @author Simon Bennett <simon@pixelatedcrow.com>
 */
final class ExportEventStore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smoothphp:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export EventStore';

    /** @var DatabaseManager */
    private $databaseManager;

    /** @var Repository */
    private $config;

    /**
     * ExportEventStore constructor.
     * @param DatabaseManager $databaseManager
     * @param Repository $config
     */
    public function __construct(DatabaseManager $databaseManager, Repository $config)
    {
        $this->databaseManager = $databaseManager;
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
        echo json_encode($this->getAllEvents());
    }

    /**
     * @return \stdClass
     */
    private function getAllEvents()
    {
        return $this->databaseManager
            ->connection($this->config->get('cqrses.eventstore_connection'))
            ->table($this->config->get('cqrses.eventstore_table'))
            ->get();
    }
}
