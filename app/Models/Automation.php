<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Automation extends Model
{
    protected $guarded = [];
    protected $casts = ['channels' => 'array', 'subject' => 'array', 'body' => 'array', 'is_promo' => 'boolean', 'active' => 'boolean'];
    public function course() { return $this->belongsTo(Course::class); }
    public function promote() { return $this->belongsTo(Course::class, 'promote_course_id'); }
    public function messageLogs() { return $this->hasMany(MessageLog::class); }

    // T-01: an automation with message history can't be deleted (message_logs.automation_id is RESTRICT); deactivate it instead.
    protected static function booted(): void { static::deleting(fn (self $a) => $a->canBeDeleted()); }
    public function canBeDeleted(): bool { return ! $this->messageLogs()->exists(); }
}
