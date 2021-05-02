<?php

namespace Cerotechsys\Seedercsv\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class SeederCsvCommand extends GeneratorCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:seedercsv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new seeder class';

    /**
     * The connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Seeder';

    /**
     * csvPath variable
     *
     * @var string
     */
    protected $csvPath = null;

    /**
     * Create a new controller creator command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct($files);

        $this->addOption('--connection',
                '-c',
                InputOption::VALUE_OPTIONAL,
                'Set the connection name defined in the database configuration file.',
                config('database.default')
            )
            ->addOption('--table',
                '-t',
                InputOption::VALUE_OPTIONAL,
                'If you select more than one, you need to separate them with commas.'
            );
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // First we need to ensure that the given name is not a reserved word within the PHP
        // language and that the class name will actually be valid. If it is not valid we
        // can error now and prevent from polluting the filesystem using invalid files.
        if ($this->isReservedName($this->getNameInput())) {
            $this->error('The name "'.$this->getNameInput().'" is reserved by PHP.');

            return false;
        }

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
        $this->csvPath = "seeders/Csv/{$this->argument('name')}/empty";
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
    }

    /**
     * generate function
     *
     * @param string $name
     * @param string $path
     *
     * @return void
     */
    protected function generate(
        string $name,
        string $path
    ) {

        // csv directory
        $this->makeDirectory(
            $this->laravel->databasePath(
                $this->csvPath
            )
        );

        $this->files->put($path, $this->sortImports($this->buildClass($name)));

        if (! $this->option('table')) {

            $this->files->put(
                $this->laravel->databasePath(
                    $this->csvPath
                ),
                ''
            );

        } else {

            foreach (explode(',', $this->option('table')) as $table) {
                $this->files->put(
                    $this->laravel->databasePath(
                        dirname($this->csvPath) . '/'
                        . sprintf('%s_%s_table.csv',
                            date_create()
                                ->format('Y_m_d_u'),
                            $table
                        )
                    ),
                    "col1,col2,col3,created_at,updated_at\r\ncol1,,,,\r\n1,test01,{`php:bcrypt('test1')`},{`php:DB::raw('now()')`},{`php:DB::raw('now()')`}\r\n2,test02,{`php:bcrypt('test2')`},{`php:now()`},{`php:now()`}"
                );
            }

        }

    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/../../stubs/seedercsv.stub';
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return is_file($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
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

        return $this->laravel->databasePath().'/seeders/'.$name.'.php';
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

        return "Database\\Seeders\\{$name}";
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceNamespace(&$stub, $name)
    {
        $searches = [
            [
                'DummyNamespace',
                'DummyRootNamespace',
                'NamespacedDummyUserModel',
                'DummyClass',
                'DummyCsvPath',
                'DummyConnection',
            ], [
                '{{ namespace }}',
                '{{ rootNamespace }}',
                '{{ namespacedUserModel }}',
                '{{ class }}',
                '{{ csvPath }}',
                '{{ connection }}',
            ], [
                '{{namespace}}',
                '{{rootNamespace}}',
                '{{namespacedUserModel}}',
                '{{class}}',
                '{{csvPath}}',
                '{{connection}}',
            ],
        ];

        foreach ($searches as $search) {
            $stub = str_replace(
                $search, [
                    $this->getNamespace($name),
                    $this->rootNamespace(),
                    $this->userProviderModel(),
                    basename(str_replace('\\', '/', $name)),
                    dirname($this->csvPath),
                    $this->option('connection'),
                ],
                $stub
            );
        }

        return $this;
    }

}
