<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $guarded = [];
    protected $casts = ['starts_at' => 'datetime'];
    public function session() { return $this->belongsTo(CourseSession::class, 'course_session_id'); }

    // T-01: message_logs.meeting_id is a sentinel column without FK, so guard here: no meeting deletion once the session has registrations.
    // (DB cascades from deleting an empty session do not fire model events, so empty sessions still delete normally.)
    protected static function booted(): void
    {
        static::deleting(fn (self $m) => ! ($m->session?->registrations()->exists() ?? false));
    }
}
