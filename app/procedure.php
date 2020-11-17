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

        public function citas_medicas() {
            return $this->belongsToMany('App\citas_medicas')->withPivot('cantidad');
        }

}
