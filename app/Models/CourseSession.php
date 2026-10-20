<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CourseSession extends Model
{
    protected $guarded = [];
    protected $casts = ['starts_at' => 'datetime'];

    protected static function booted(): void
    {
        // T-01: refuse deletion while registrations exist (the registrations.course_session_id RESTRICT FK is the hard guarantee).
        static::deleting(fn (self $s) => $s->canBeDeleted());
        // The program is fixed: one class per week for course->weeks (6 by default), starting on starts_at.
        static::created(function (self $s) {
            if ($s->meetings()->exists()) return;
            for ($i = 0; $i < ($s->course->weeks ?: 6); $i++)
                $s->meetings()->create(['n' => $i + 1, 'starts_at' => $s->starts_at->copy()->addWeeks($i)]);
        });
    }
    public function course() { return $this->belongsTo(Course::class); }
    public function meetings() { return $this->hasMany(Meeting::class)->orderBy('starts_at'); }
    public function registrations() { return $this->hasMany(Registration::class); }
    public function canBeDeleted(): bool { return ! $this->registrations()->exists(); }   // otherwise close it (status = closed)

    /**
     * T-01.1: ids of persisted meetings that a submitted meetings-repeater state would remove, only when the session has registrations.
     * Filament 3.x keys persisted repeater rows as "record-{id}" (Repeater::relationship() save logic).
     */
    public function meetingIdsRemovedBy(array $submittedItemKeys): array
    {
        if (! $this->registrations()->exists()) return [];
        return $this->meetings()->pluck('id')->reject(fn ($id) => in_array("record-{$id}", $submittedItemKeys, true))->values()->all();
    }
    public function taken(): int { return $this->registrations()->where('status', '!=', 'cancelled')->count(); }
    public function seatsLeft(): int { return max(0, $this->seats - $this->taken()); }
    public function currentPrice(): int
    {
        $early = $this->early_price !== null && max(0, $this->early_seats - $this->taken()) > 0;
        return $early ? $this->early_price : $this->price;
    }
}
