<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    // This is a dummy model for Filament resource
    // No database table is associated with this model
    protected $fillable = [
        'report_type',
        'month',
        'year',
    ];
    
    // Prevents Laravel from trying to interact with a database table
    protected $table = null;
    
    // Disable timestamps
    public $timestamps = false;
}