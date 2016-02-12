<?php
namespace SmoothPhp\LaravelAdapter\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\DatabaseManager;

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
    protected $signature = 'smoothphp:import';

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
        $fd = fopen("php://stdin", "r");
        $eventsJson = "";
        while (!feof($fd)) {
            $eventsJson .= fread($fd, 1024);
        }

        fclose($fd);

        $events = json_decode($eventsJson, true);


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
     *
     */
    private function truncate()
    {
        $this->databaseManager
            ->connection($this->config->get('cqrses.eventstore_connection'))
            ->table($this->config->get('cqrses.eventstore_table'))
            ->truncate();
    }
}
