<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
        'entity_id' => 'integer',
    ];

    /**
     * Get the user who performed this action
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related entity (polymorphic)
     */
    public function entity()
    {
        if ($this->entity_type && $this->entity_id) {
            $modelClass = 'App\\Models\\' . $this->entity_type;
            if (class_exists($modelClass)) {
                return $modelClass::find($this->entity_id);
            }
        }
        return null;
    }

    /**
     * Scope to get logs for a specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get logs for a specific action
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to get logs for a specific entity
     */
    public function scopeForEntity($query, string $entityType, int $entityId = null)
    {
        $query = $query->where('entity_type', $entityType);

        if ($entityId) {
            $query->where('entity_id', $entityId);
        }

        return $query;
    }

    /**
     * Create a new activity log entry
     */
    public static function logActivity(
        ?int $userId,
        string $action,
        string $description,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $metadata = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        return static::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $ipAddress ?: request()->ip(),
            'user_agent' => $userAgent ?: request()->userAgent(),
        ]);
    }

    /**
     * Log user login
     */
    public static function logLogin(int $userId, ?string $ipAddress = null): self
    {
        return static::logActivity(
            $userId,
            'login',
            'User logged in',
            'User',
            $userId,
            null,
            $ipAddress
        );
    }

    /**
     * Log user logout
     */
    public static function logLogout(int $userId): self
    {
        return static::logActivity(
            $userId,
            'logout',
            'User logged out',
            'User',
            $userId
        );
    }

    /**
     * Log survey started
     */
    public static function logSurveyStarted(int $userId, int $surveyId, int $responseId): self
    {
        return static::logActivity(
            $userId,
            'survey_started',
            'Started survey',
            'Survey',
            $surveyId,
            ['response_id' => $responseId]
        );
    }

    /**
     * Log survey completed
     */
    public static function logSurveyCompleted(int $userId, int $surveyId, int $responseId): self
    {
        return static::logActivity(
            $userId,
            'survey_completed',
            'Completed survey',
            'Survey',
            $surveyId,
            ['response_id' => $responseId]
        );
    }

    /**
     * Log profile updated
     */
    public static function logProfileUpdated(int $userId, array $changes = []): self
    {
        return static::logActivity(
            $userId,
            'profile_updated',
            'Updated profile',
            'AlumniProfile',
            null,
            ['changes' => $changes]
        );
    }
}
