<?php

namespace Cerotechsys\Seedercsv\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;

class LegacySeederCsvCommand extends SeederCsvCommand
{

    /**
     * composer variable
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer = null;

    /**
     * Create a new controller creator command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Support\Composer  $composer
     *
     * @return void
     */
    public function __construct(
        Filesystem $files,
        Composer $composer
    ) {
        parent::__construct($files);

        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $name = $this->qualifyClass($this->getNameInput());

        $path = $this->getPath($name);

        // Next, We will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if ((! $this->hasOption('force') ||
             ! $this->option('force')) &&
             $this->alreadyExists($this->getNameInput())) {
            $this->error($this->type.' already exists!');

            return false;
        }

        // Next, we will generate the path to the location where this class' file should get
        // written. Then, we will build the class and make the proper replacements on the
        // stub files so that it gets the correctly formatted namespace and class name.
        $this->makeDirectory($path);

        // Generate class and csv files.
        $this->csvPath = "seeds/Csv/{$this->argument('name')}/empty";
        $this->generate(
            $name,
            $path
        );

        $this->info(
            sprintf(
                'Execution command : php artisan db:seed --class=\%s\%s --database=%s',
                $this->getNamespace($name),
                basename(str_replace('\\', '/', $name)),
                $this->option('connection')
            )
        );

        $this->info($this->type.' created successfully.');

        $this->composer->dumpAutoloads();
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = $this->argument('name');

        return $this->laravel->databasePath().'/seeds/'.$name.'.php';
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        if (strpos($name, '/') !== false) {
            $name = str_replace('/', '\\', $name);
        }

        return "Cerotechsys\\Seedercsv\\Seeds\\{$name}";
    }

}
