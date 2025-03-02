<?php

namespace App\Models;

use App\Mail\InvitationMail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class Invitation extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'apartment_id',
        'email',
        'expires_at',
    ];
    
    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];
    
    /**
     * Get the apartment this invitation belongs to
     */
    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }
    
    /**
     * Check if the invitation is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
    
    /**
     * Check if the invitation has been used
     */
    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }
    
    /**
     * Mark the invitation as used
     */
    public function markAsUsed(): void
    {
        $this->used_at = now();
        $this->save();
    }
    
    /**
     * Send the invitation email
     */
    public function sendInvitationEmail(): void
    {
        try {
            Mail::to($this->email)->send(new InvitationMail($this));
        } catch (\Exception $e) {
            // Log the error but don't crash the application
            Log::error('Failed to send invitation email: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate a unique code for the invitation
     */
    protected static function booted()
    {
        static::creating(function (Invitation $invitation) {
            $invitation->code = Str::random(16);
            
            // Default expiration is 7 days from now if not set
            if (!$invitation->expires_at) {
                $invitation->expires_at = now()->addDays(7);
            }
        });
        
        static::created(function (Invitation $invitation) {
            $invitation->sendInvitationEmail();
        });
    }
}
