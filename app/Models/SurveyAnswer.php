<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_response_id',
        'survey_question_id',
        'answer_text',
        'answer_json',
        'answer_number',
        'answer_date',
        'answer_boolean',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'answered_at',
        'time_spent_seconds',
        'is_skipped',
        'notes',
    ];

    protected $casts = [
        'answer_json' => 'array',
        'answer_number' => 'decimal:4',
        'answer_date' => 'datetime',
        'answer_boolean' => 'boolean',
        'answered_at' => 'datetime',
        'time_spent_seconds' => 'integer',
        'file_size' => 'integer',
        'is_skipped' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->answered_at) {
                $model->answered_at = now();
            }
        });
    }

    /**
     * Get the survey response this answer belongs to
     */
    public function surveyResponse()
    {
        return $this->belongsTo(SurveyResponse::class);
    }

    /**
     * Get the survey question this answer is for
     */
    public function surveyQuestion()
    {
        return $this->belongsTo(SurveyQuestion::class);
    }

    /**
     * Get the formatted answer value based on question type
     */
    public function getFormattedAnswerAttribute()
    {
        $question = $this->surveyQuestion;

        if (!$question) {
            return $this->answer_text;
        }

        switch ($question->question_type) {
            case 'text':
            case 'textarea':
            case 'email':
            case 'phone':
                return $this->answer_text;

            case 'number':
            case 'rating':
                return $this->answer_number;

            case 'date':
                return $this->answer_date ? $this->answer_date->format('Y-m-d') : null;

            case 'single_choice':
            case 'dropdown':
                return $this->answer_text;

            case 'multiple_choice':
            case 'checkbox':
                return $this->answer_json ?? [];

            case 'file_upload':
                return [
                    'file_name' => $this->file_name,
                    'file_path' => $this->file_path,
                    'file_size' => $this->file_size,
                    'file_type' => $this->file_type,
                ];

            case 'matrix':
                return $this->answer_json ?? [];

            default:
                return $this->answer_text;
        }
    }

    /**
     * Set answer value based on question type
     */
    public function setAnswerValue($value, SurveyQuestion $question): void
    {
        // Clear all answer fields first
        $this->answer_text = null;
        $this->answer_json = null;
        $this->answer_number = null;
        $this->answer_date = null;
        $this->answer_boolean = null;

        switch ($question->question_type) {
            case 'text':
            case 'textarea':
            case 'email':
            case 'phone':
            case 'single_choice':
            case 'dropdown':
                $this->answer_text = $value;
                break;

            case 'number':
                $this->answer_number = is_numeric($value) ? (float) $value : null;
                break;

            case 'rating':
                $this->answer_number = is_numeric($value) ? (int) $value : null;
                break;

            case 'date':
                $this->answer_date = $value;
                break;

            case 'multiple_choice':
            case 'checkbox':
            case 'matrix':
                $this->answer_json = is_array($value) ? $value : [$value];
                break;

            case 'file_upload':
                // File upload handling would be done separately
                if (is_array($value)) {
                    $this->file_name = $value['file_name'] ?? null;
                    $this->file_path = $value['file_path'] ?? null;
                    $this->file_type = $value['file_type'] ?? null;
                    $this->file_size = $value['file_size'] ?? null;
                }
                break;

            default:
                $this->answer_text = $value;
        }
    }

    /**
     * Check if answer has a value
     */
    public function hasValue(): bool
    {
        return !is_null($this->answer_text) ||
            !is_null($this->answer_json) ||
            !is_null($this->answer_number) ||
            !is_null($this->answer_date) ||
            !is_null($this->answer_boolean) ||
            !is_null($this->file_path);
    }
}
