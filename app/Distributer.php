<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Distributer extends Model
{
    protected $fillable = [
        'id', 'user_id', 'Latitude', 'Longitude', 'Food', 'Mask', 'Other'
    ]; 
}
