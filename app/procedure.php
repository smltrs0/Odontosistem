<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class procedure extends Model
{
    protected $fillable= [
    	'id',
    	'key_p',
        'title',
        'className',
        'code',
        'price',
        'apply',
        'clearBefore'
        
        ];
}
