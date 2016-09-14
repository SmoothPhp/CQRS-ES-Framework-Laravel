<?php
namespace SmoothPhp\LaravelAdapter\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\DatabaseManager;
use SmoothPhp\LaravelAdapter\Exception\EventStoreFileNotFound;

/**
 * Class ImportEventStore
 * @package SmoothPhp\LaravelAdapter\Console
 * @author Simon Bennett <simon@bennett.im>
 */
final class ImportEventStore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smoothphp:import {--file= : If specified, import from a JSON file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import EventStore';

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
        $events = json_decode(!!$this->option('file') ? $this->readFromJsonFile() : $this->readFromStdIn(), true);

        $this->truncate();

        foreach ($events as $event) {
            $this->databaseManager
                ->connection($this->config->get('cqrses.eventstore_connection'))
                ->table($this->config->get('cqrses.eventstore_table'))
                ->insert($event);
        }

        $this->call('smoothphp:rebuild');

        return $this->line('Success');

    }

    /**
     *  Truncate (empty) event store table
     */
    private function truncate()
    {
        $this->databaseManager
            ->connection($this->config->get('cqrses.eventstore_connection'))
            ->table($this->config->get('cqrses.eventstore_table'))
            ->truncate();
    }

    /**
     * Read event store JSON from std in
     *
     * @return string
     */
    private function readFromStdIn()
    {
        $fd = fopen("php://stdin", "r");
        $eventsJson = "";
        while (!feof($fd)) {
            $eventsJson .= fread($fd, 1024);
        }

        fclose($fd);

        return $eventsJson;
    }

    /**
     * Read event store JSON from file
     *
     * @return string
     */
    private function readFromJsonFile()
    {
        if (!file_exists($path = $this->option('file'))) {
            throw new EventStoreFileNotFound(sprintf('%s/%s', __DIR__, $path));
        }

        return file_get_contents($path);
    }
}
