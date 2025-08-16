<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'instructions',
        'status',
        'type',
        'start_date',
        'end_date',
        'target_batches',
        'target_graduation_years',
        'is_anonymous',
        'allow_multiple_responses',
        'require_authentication',
        'is_registration_survey',
        'email_subject',
        'email_body',
        'send_reminder_emails',
        'reminder_interval_days',
        'total_sent',
        'total_responses',
        'response_rate',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'target_batches' => 'array',
        'target_graduation_years' => 'array',
        'is_anonymous' => 'boolean',
        'allow_multiple_responses' => 'boolean',
        'require_authentication' => 'boolean',
        'is_registration_survey' => 'boolean',
        'send_reminder_emails' => 'boolean',
        'reminder_interval_days' => 'integer',
        'total_sent' => 'integer',
        'total_responses' => 'integer',
        'response_rate' => 'decimal:2',
    ];

    /**
     * Get the user who created this survey
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all questions for this survey
     */
    public function questions()
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('order');
    }

    /**
     * Get all responses for this survey
     */
    public function responses()
    {
        return $this->hasMany(SurveyResponse::class);
    }

    /**
     * Get all invitations for this survey
     */
    public function invitations()
    {
        return $this->hasMany(SurveyInvitation::class);
    }

    /**
     * Scope to get active surveys
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get registration surveys
     */
    public function scopeRegistration($query)
    {
        return $query->where('is_registration_survey', true);
    }

    /**
     * Check if survey is currently active
     */
    public function isCurrentlyActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();

        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Update response statistics
     */
    public function updateResponseStats(): void
    {
        $totalSent = $this->invitations()->count();
        $totalResponses = $this->responses()->where('status', 'completed')->count();
        $responseRate = $totalSent > 0 ? ($totalResponses / $totalSent) * 100 : 0;

        $this->update([
            'total_sent' => $totalSent,
            'total_responses' => $totalResponses,
            'response_rate' => $responseRate,
        ]);
    }
}
