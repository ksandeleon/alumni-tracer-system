<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'graduation_year',
        'description',
        'status',
    ];

    protected $casts = [
        'graduation_year' => 'integer',
    ];

    /**
     * Get all alumni profiles for this batch
     */
    public function alumniProfiles()
    {
        return $this->hasMany(AlumniProfile::class);
    }

    /**
     * Get survey invitations for this batch
     */
    public function surveyInvitations()
    {
        return $this->hasMany(SurveyInvitation::class);
    }

    /**
     * Scope to get active batches
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get alumni count for this batch
     */
    public function getAlumniCountAttribute(): int
    {
        return $this->alumniProfiles()->count();
    }
}
