<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bug extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'steps_to_reproduce',
        'browser_info',
        'screenshot_path',
    ];

    /**
     * Get the user who reported the bug
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
