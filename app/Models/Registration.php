<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Registration extends Model
{
    protected $guarded = [];
    protected $casts = ['addons' => 'array', 'paid_at' => 'datetime', 'confirmed_at' => 'datetime', 'consent' => 'boolean'];
    protected static function booted(): void
    {
        static::creating(fn ($r) => $r->uuid ??= (string) Str::uuid());
        // T-01.1: record the FIRST confirmation once; never clear or overwrite it afterwards (status may go back to pending/cancelled).
        // `saving` fires for every Eloquent create()/save()/update() (public form, markPaid(), webhook/return, Filament create/edit).
        // It does NOT fire for query-builder or mass updates/inserts.
        static::saving(function (self $r) {
            $first = $r->getOriginal('confirmed_at');
            if ($first !== null) { $r->confirmed_at = $first; }
            elseif ($r->status === 'confirmed' && $r->confirmed_at === null) { $r->confirmed_at = now(); }
        });
        static::deleting(fn (self $r) => $r->canBeDeleted());   // T-01
    }

    /** Historical record: was ever confirmed (incl. free), is confirmed, is paid, or is linked to a payment invoice. */
    public function isProtected(): bool
    {
        return $this->confirmed_at !== null || $this->status === 'confirmed' || $this->paid_at !== null || $this->invoice_id !== null;
    }

    public function canBeDeleted(): bool { return ! $this->isProtected(); }
    public function messageLogs() { return $this->hasMany(MessageLog::class); }
    public function getRouteKeyName(): string { return 'uuid'; }
    public function course() { return $this->belongsTo(Course::class); }
    public function session() { return $this->belongsTo(CourseSession::class, 'course_session_id'); }
    public function markPaid(): void
    {
        if ($this->status !== 'confirmed') $this->update(['status' => 'confirmed', 'paid_at' => now()]);
    }
}
