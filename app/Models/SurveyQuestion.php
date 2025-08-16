<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'question_text',
        'description',
        'question_type',
        'options',
        'validation_rules',
        'is_required',
        'order',
        'is_active',
        'conditional_logic',
        'matrix_rows',
        'matrix_columns',
        'rating_min',
        'rating_max',
        'rating_min_label',
        'rating_max_label',
        'placeholder',
        'help_text',
    ];

    protected $casts = [
        'options' => 'array',
        'validation_rules' => 'array',
        'is_required' => 'boolean',
        'order' => 'integer',
        'is_active' => 'boolean',
        'conditional_logic' => 'array',
        'matrix_rows' => 'array',
        'matrix_columns' => 'array',
        'rating_min' => 'integer',
        'rating_max' => 'integer',
    ];

    /**
     * Get the survey this question belongs to
     */
    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    /**
     * Get all answers for this question
     */
    public function answers()
    {
        return $this->hasMany(SurveyAnswer::class);
    }

    /**
     * Scope to get active questions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get required questions
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Check if question is a choice type
     */
    public function isChoiceType(): bool
    {
        return in_array($this->question_type, [
            'single_choice',
            'multiple_choice',
            'dropdown',
            'checkbox'
        ]);
    }

    /**
     * Check if question accepts multiple answers
     */
    public function acceptsMultipleAnswers(): bool
    {
        return in_array($this->question_type, ['multiple_choice', 'checkbox']);
    }

    /**
     * Get formatted options for frontend
     */
    public function getFormattedOptionsAttribute(): array
    {
        if (!$this->isChoiceType() || !$this->options) {
            return [];
        }

        return collect($this->options)->map(function ($option, $index) {
            return [
                'value' => is_array($option) ? ($option['value'] ?? $index) : $index,
                'label' => is_array($option) ? ($option['label'] ?? $option) : $option,
            ];
        })->toArray();
    }
}
