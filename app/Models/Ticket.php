<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'status',
        'category_id',
        'user_id',
        'assigned_to',
        'attachments',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($ticket) {
            // Auto-set resolved_at when status changes to resolved
            if ($ticket->isDirty('status')) {
                if ($ticket->status === 'resolved' && $ticket->getOriginal('status') !== 'resolved') {
                    $ticket->resolved_at = now();
                } elseif ($ticket->status === 'closed' && $ticket->getOriginal('status') !== 'closed') {
                    $ticket->closed_at = now();
                    if (!$ticket->resolved_at) {
                        $ticket->resolved_at = now();
                    }
                }
            }
        });
    }

    /**
     * Get the category this ticket belongs to
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the user who created this ticket
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user this ticket is assigned to
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get comments for this ticket
     */
    public function comments()
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get activity logs for this ticket
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus(Builder $query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by priority
     */
    public function scopeByPriority(Builder $query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for filtering by category
     */
    public function scopeByCategory(Builder $query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope for filtering by assigned user
     */
    public function scopeAssignedTo(Builder $query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope for search
     */
    public function scopeSearch(Builder $query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Get priority color class
     */
    public function getPriorityColorAttribute()
    {
        return match($this->priority) {
            'low' => 'text-green-600 bg-green-100',
            'medium' => 'text-yellow-600 bg-yellow-100',
            'high' => 'text-red-600 bg-red-100',
            default => 'text-gray-600 bg-gray-100',
        };
    }

    /**
     * Get status color class
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'open' => 'text-blue-600 bg-blue-100',
            'in_progress' => 'text-orange-600 bg-orange-100',
            'resolved' => 'text-green-600 bg-green-100',
            'closed' => 'text-gray-600 bg-gray-100',
            default => 'text-gray-600 bg-gray-100',
        };
    }
}