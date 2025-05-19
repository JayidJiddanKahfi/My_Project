<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $table = 'expenses';

    protected $fillable = [
        'expense_date',
        'description',
        'amount',
        'recorded_by',
    ];

    public function user(){
        return $this->belongsTo(User::class,'recorded_by');
    }
}
