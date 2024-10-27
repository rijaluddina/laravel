<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShoePhoto extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'shoe_id',
        'photo',
    ];
}
