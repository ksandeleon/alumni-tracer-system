<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlumniProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'batch_id',
        'first_name',
        'last_name',
        'middle_name',
        'student_id',
        'birth_date',
        'gender',
        'phone',
        'alternate_email',
        'current_address',
        'city',
        'state_province',
        'postal_code',
        'country',
        'degree_program',
        'major',
        'minor',
        'gpa',
        'graduation_year',
        'graduation_date',
        'employment_status',
        'current_job_title',
        'current_employer',
        'company_industry',
        'company_size',
        'current_salary',
        'salary_currency',
        'job_start_date',
        'job_description',
        'job_related_to_degree',
        'skills',
        'certifications',
        'career_goals',
        'feedback_to_institution',
        'willing_to_mentor',
        'willing_to_hire_alumni',
        'profile_completed',
        'profile_completed_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'graduation_date' => 'date',
        'job_start_date' => 'date',
        'gpa' => 'decimal:2',
        'current_salary' => 'decimal:2',
        'job_related_to_degree' => 'boolean',
        'willing_to_mentor' => 'boolean',
        'willing_to_hire_alumni' => 'boolean',
        'profile_completed' => 'boolean',
        'profile_completed_at' => 'datetime',
        'skills' => 'array',
        'certifications' => 'array',
    ];

    /**
     * Get the user that owns this profile
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the batch this alumni belongs to
     */
    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . ($this->middle_name ? $this->middle_name . ' ' : '') . $this->last_name);
    }

    /**
     * Check if profile is complete
     */
    public function isProfileComplete(): bool
    {
        $requiredFields = [
            'first_name',
            'last_name',
            'degree_program',
            'major',
            'graduation_year',
            'employment_status'
        ];

        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Mark profile as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'profile_completed' => true,
            'profile_completed_at' => now(),
        ]);
    }
}
