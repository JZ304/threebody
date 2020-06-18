<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplyModel extends Model
{
    //
    protected $table = 'user_apply';

    protected $dateFormat = 'U';

    protected $fillable = ['user_id','money','remark','apply_at'];

}
