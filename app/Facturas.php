<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Facturas extends Model
{
    protected $fileable = [
        'total_neto',
    ];


    public function Abonos()
    {
        return $this->hasMany('App\Abonos');
    }

}
