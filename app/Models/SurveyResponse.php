<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SurveyResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'user_id',
        'response_token',
        'status',
        'started_at',
        'completed_at',
        'last_updated_at',
        'respondent_email',
        'respondent_name',
        'respondent_student_id',
        'ip_address',
        'user_agent',
        'browser_info',
        'total_questions',
        'answered_questions',
        'completion_percentage',
        'time_spent_seconds',
        'is_valid_response',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_updated_at' => 'datetime',
        'browser_info' => 'array',
        'total_questions' => 'integer',
        'answered_questions' => 'integer',
        'completion_percentage' => 'decimal:2',
        'time_spent_seconds' => 'integer',
        'is_valid_response' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->response_token) {
                $model->response_token = Str::random(40);
            }
            if (!$model->started_at) {
                $model->started_at = now();
            }
            if (!$model->last_updated_at) {
                $model->last_updated_at = now();
            }
        });

        static::updating(function ($model) {
            $model->last_updated_at = now();
        });
    }

    /**
     * Get the survey this response belongs to
     */
    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    /**
     * Get the user who submitted this response
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all answers for this response
     */
    public function answers()
    {
        return $this->hasMany(SurveyAnswer::class);
    }

    /**
     * Scope to get completed responses
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get in-progress responses
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Mark response as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completion_percentage' => 100.00,
        ]);
    }

    /**
     * Update completion progress
     */
    public function updateProgress(): void
    {
        $totalQuestions = $this->survey->questions()->active()->count();
        $answeredQuestions = $this->answers()->count();
        $completionPercentage = $totalQuestions > 0 ? ($answeredQuestions / $totalQuestions) * 100 : 0;

        $this->update([
            'total_questions' => $totalQuestions,
            'answered_questions' => $answeredQuestions,
            'completion_percentage' => $completionPercentage,
        ]);
    }

    /**
     * Get answer for a specific question
     */
    public function getAnswerForQuestion(int $questionId): ?SurveyAnswer
    {
        return $this->answers()->where('survey_question_id', $questionId)->first();
    }

    /**
     * Check if response is complete
     */
    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get respondent name (from user or directly stored)
     */
    public function getRespondentNameAttribute(): ?string
    {
        if ($this->user) {
            $profile = $this->user->alumniProfile;
            if ($profile) {
                return $profile->full_name;
            }
        }

        return $this->attributes['respondent_name'] ?? null;
    }
}
