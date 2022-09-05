<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class Tournament extends Model
{
    //public $timestamps = false;//desabilitar created_at && updated_at
    const UPDATED_AT = null;//desabilitar sólo updated_at

}
