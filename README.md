## Laravel Seedercsv

## Server Requirements
* PHP >= 7.3, Laravel 8

## Installation

Require this package with composer.
```shell
composer require cerotechsys/seedercsv
```

Executing the migrate to generate a `seeds` table.
```shell
php artisan migrate
```

Seed structure
```sql
CREATE TABLE `seeds` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `class` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
    `file` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
    `batch` INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`) USING BTREE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;
```

Class used for CSV data registration.
```php
\Illuminate\Database\Query::updateOrInsert(array $attributes, array $values = [])
```

## Usage
The added artisan make command.
```shell
$ php artisan make:seedercsv
```

### option
Create a seeding class with a table.
```shell
$ php artisan make:seedercsv Master/Dir/ExampleSeeder --table=users
```

If you need to specify multiple tables, define them separated by commas.
```shell
$ php artisan make:seedercsv Master/Dir/ExampleSeeder --table=users,blogs,...
```

Define the CSV file name. `yy_mm_dd_<6 digits of microseconds>_<table name>_table.csv`

Below is a csv data sample.
```
col1,col2,col3,created_at,updated_at
col1,,,,
1,test01,{`php:bcrypt('test1')`},{`php:DB::raw('now()')`},{`php:DB::raw('now()')`}
2,test02,test02@example.jp,{`php:bcrypt('test2')`},{`php:now()`},{`php:now()`}
```

* Line 1: First name Defines the field name.
* Second line: Defines the field name used in the condition.
* 3rd line: When embedding PHP code, please define it referring to the right. : ```{`php: <php code>`}```

## Example

Create a Seeder class for data registration.
```shell
$ php artisan make:seedercsv Master/Dir/ExampleSeeder --table=users
```

Generated file
* CSV file : `database/seeders/Csv/Master/Dir/ExampleSeeder/2021_04_29_554693_users_table.csv`
  * If there are multiple target tables for data registration, save them in the csv file `ExampleSeeder` folder according to the file naming convention.
* Class file : `database/seeders/Master/Dir/ExampleSeeder.php`

### Let's take a look at the generated ExampleSeeder.php class
* The command to execute : `php artisan db:seed --class=\Database\Seeders\Master\Dir\ExampleSeeder --database=mysql`
* Change the class file if necessary.
```php
<?php
/**
 * Seeder class
 *
 * File name (eg : 2021_04_01_000001_users_table.csv
 * Csv file path : seeders/Csv/Master/Dir/ExampleSeeder
 * Execution command : php artisan db:seed --class=\Database\Seeders\Master\Dir\ExampleSeeder --database=mysql
 */
namespace Database\Seeders\Master\Dir;

use Cerotechsys\Seedercsv\Services\Csv\ParseService;
use Exception;
use Illuminate\Database\Seeder as BaseSeeder;
use Illuminate\Support\Facades\DB;

class ExampleSeeder extends BaseSeeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $dir = database_path('seeders/Csv/Master/Dir/ExampleSeeder');
        $files = glob("{$dir}/*.*");
        $tblSeed = 'seeds';

        $db = DB::connection('mysql');
        $db->beginTransaction();

        try {
            $batchNumber = ($db->table($tblSeed)
                ->max('batch') ?? 0) + 1;

            foreach ($files as $file) {
                $table = preg_replace(
                    '/(^\d{4}_\d{2}_\d{2}_\d{6}_|_table\.csv$)/i',
                    '',
                    basename($file)
                );
                $data = (new ParseService($file))
                    ->create();

                foreach ($data['data'] as $key => $value) {

                    foreach ($value as &$v) {
                        preg_match('/{`php:([^`]+)`}/i', $v, $matches);

                        if (isset($matches[1]) === true) {

                            if (str_ends_with($matches[1], ';') === false) {
                                $matches[1] .= ';';
                            }

                            eval("\$v = {$matches[1]}");
                        }

                    }

                    $db->table($table)
                        ->updateOrInsert(
                            $data['cond'][$key],
                            $value
                        );

                }

                $dataSeed = collect([
                    'class'     => '\\'.__CLASS__,
                    'file'      => str_replace(storage_path('app'), '', $file),
                    'batch'     => $batchNumber,
                ]);

                if (! $db->table($tblSeed)
                    ->insert($dataSeed->toArray())
                ) {
                    throw new Exception("File {$tblSeed} : {$dataSeed->toJson()}");
                }
            }

            $db->commit();

        } catch(Exception $e) {
            $db->rollback();

            $this->command->error(
                $e->getMessage()
            );
        }
    }

}
```
