<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fee extends Model
{
    protected $table = 'fees';

    protected $fillable = [
        'contribution_amount',
        'social_fund',
        'total_amount',
    ];
    

}
