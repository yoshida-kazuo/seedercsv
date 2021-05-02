<?php

namespace Cerotechsys\Seedercsv\Console\Commands;

use Cerotechsys\Seedercsv\Models\Seed;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Exception;

class SeederCsvStatusCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seedercsv:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Shows the last applied Seeding files.';

    /**
     * Create a new console command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->addOption('--connection',
                '-c',
                InputOption::VALUE_OPTIONAL,
                'Set the connection name defined in the database configuration file.',
                config('database.default')
            )
            ->addOption('--limit',
                '-l',
                InputOption::VALUE_OPTIONAL,
                'Set the number of lines to display. The default value is 15.',
                15,
            );
    }

    /**
     * handle function
     *
     * @return void
     */
    public function handle()
    {
        try {

            $seeds = Seed::on($this->option('connection'))
                ->orderByDesc('id')
                ->limit($this->option('limit'))
                ->get();

        } catch (Exception $e) {
            $this->error('Seed table not found.');

            return 1;
        }

        if ($seeds->isEmpty()) {
            $this->error('No seeds found');

            return 1;
        }

        $this->table([
                'id',
                'class',
                'file',
                'batch',
            ],
            $seeds->sortBy('id')
        );
    }

}
