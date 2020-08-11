<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UpdateDriftUser extends Model
{
    protected $table = 'drift_users';
    protected $primaryKey = 'row_id';
    public $timestamps = false;
}
