<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Pacientes extends Model
{
    //
    protected $fillable= ['name',
                    'second_name',
                    'last_name',
                    'second_last_name',
                    'dni',
                    'birth_date',
                    'phone',
                    'address',
                    'height',
                    'weight',
                    'medical_history',
                    'procedures',
                    'registered_by',
                    'sex',
                    'user_id',
                ];

    public function user()
    {
        return  $this->belongsTo('App\User');
    }
    public function citas()
    {
        return $this->hasMany('App\Citas');
    }
}
