<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';

    protected $fillable = [
        'resident_id',
        'number_of_payment_month',
        'payment_date',
        'payment_month',
        'contribution',
        'recorded_by',
    ];


    public function resident(){
        return $this->belongsTo(Resident::class,'resident_id');
    }

    public function user(){
        return $this->belongsTo(User::class,'recorded_by');
    }

    
}
