<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SurveyInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'email',
        'name',
        'student_id',
        'batch_id',
        'invitation_token',
        'status',
        'sent_at',
        'opened_at',
        'clicked_at',
        'responded_at',
        'reminder_count',
        'last_reminder_sent',
        'email_message_id',
        'email_metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'responded_at' => 'datetime',
        'reminder_count' => 'integer',
        'last_reminder_sent' => 'datetime',
        'email_metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->invitation_token) {
                $model->invitation_token = Str::random(40);
            }
        });
    }

    /**
     * Get the survey this invitation belongs to
     */
    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    /**
     * Get the batch this invitation is for
     */
    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Scope to get sent invitations
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope to get responded invitations
     */
    public function scopeResponded($query)
    {
        return $query->where('status', 'responded');
    }

    /**
     * Mark as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark as opened
     */
    public function markAsOpened(): void
    {
        if ($this->status === 'sent') {
            $this->update([
                'status' => 'opened',
                'opened_at' => now(),
            ]);
        }
    }

    /**
     * Mark as clicked
     */
    public function markAsClicked(): void
    {
        if (in_array($this->status, ['sent', 'opened'])) {
            $this->update([
                'status' => 'clicked',
                'clicked_at' => now(),
            ]);
        }
    }

    /**
     * Mark as responded
     */
    public function markAsResponded(): void
    {
        $this->update([
            'status' => 'responded',
            'responded_at' => now(),
        ]);
    }

    /**
     * Increment reminder count
     */
    public function incrementReminder(): void
    {
        $this->increment('reminder_count');
        $this->update(['last_reminder_sent' => now()]);
    }

    /**
     * Check if can send reminder
     */
    public function canSendReminder(): bool
    {
        if ($this->status === 'responded') {
            return false;
        }

        if (!$this->survey->send_reminder_emails) {
            return false;
        }

        $maxReminders = 3; // Could be configurable
        if ($this->reminder_count >= $maxReminders) {
            return false;
        }

        if ($this->last_reminder_sent) {
            $intervalDays = $this->survey->reminder_interval_days ?? 7;
            return $this->last_reminder_sent->addDays($intervalDays)->isPast();
        }

        return true;
    }

    /**
     * Generate survey URL with token
     */
    public function getSurveyUrlAttribute(): string
    {
        return url("/survey/{$this->survey_id}?token={$this->invitation_token}");
    }
}
