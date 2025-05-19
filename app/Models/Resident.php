<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use phpDocumentor\Reflection\Types\Void_;

class Resident extends Model
{
    protected $table = 'residents';

    protected $fillable = [
        'salutation',
        'full_name',
        'address',
    ];


    public function payments(){
        return $this->hasMany(Payment::class,'resident_id');
    }

}
