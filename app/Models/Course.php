<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $guarded = [];
    protected $casts = ['title' => 'array', 'content' => 'array', 'seo' => 'array', 'addons' => 'array', 'contact' => 'array', 'active' => 'boolean'];
    public function sessions() { return $this->hasMany(CourseSession::class); }
    public function registrations() { return $this->hasMany(Registration::class); }

    // T-01: DB foreign keys (RESTRICT) are the hard guarantee; this hook makes delete() refuse cleanly (returns false).
    protected static function booted(): void { static::deleting(fn (self $c) => $c->canBeDeleted()); }

    /** A course with registrations, or with an automation that already has message history, must be archived (active=false), not deleted. */
    public function canBeDeleted(): bool
    {
        return ! $this->registrations()->exists()
            && ! Automation::where('course_id', $this->id)->whereHas('messageLogs')->exists();
    }
    public function name(string $l = 'ar'): string { return $this->title[$l] ?? $this->title['ar'] ?? $this->slug; }
}
