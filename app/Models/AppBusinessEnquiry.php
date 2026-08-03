<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppBusinessEnquiry extends Model
{
    protected $fillable = ['app_id','reference','status','name','organization','email','country','project_type','message','preferred_response','phone','meta_json'];
    protected $casts = ['meta_json' => 'array'];
}
