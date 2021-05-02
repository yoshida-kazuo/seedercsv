<?php

namespace Cerotechsys\Seedercsv\Models;

use Illuminate\Database\Eloquent\Model;

class Seed extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'seeds';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

}
