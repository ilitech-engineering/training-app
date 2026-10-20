<?php
namespace App\Console\Commands;
use App\Jobs\SendMessage;
use App\Models\{Automation, MessageLog, Registration};
use Illuminate\Console\Command;

/** Runs every minute (routes/console.php). A message is due at (anchor time + offset) and is sent once, within a 6h catch-up window. */
class RunAutomations extends Command
{
    protected $signature = 'automations:run';
    protected $description = 'Queue due emails / WhatsApp / SMS';

    public function handle(): void
    {
        foreach (Automation::with('course')->where('active', true)->get() as $a) {
            $to = now()->subMinutes($a->offset_minutes);
            $from = $to->copy()->subHours(6);
            $q = Registration::with(['course', 'session.meetings'])->where('status', '!=', 'cancelled');
            if ($a->course_id) $q->where('course_id', $a->course_id);
            if ($a->audience === 'free') $q->where('amount', 0);
            if ($a->audience === 'paid') $q->where('amount', '>', 0);
            if ($a->is_promo) $q->where('consent', true);
            match ($a->anchor) {
                'registration' => $q->whereBetween('created_at', [$from, $to]),
                'paid' => $q->where('status', 'confirmed')->whereBetween('paid_at', [$from, $to]),
                'meeting' => $q->where('status', 'confirmed')->whereHas('session.meetings', fn ($m) => $m->whereBetween('starts_at', [$from, $to])),
            };
            foreach ($q->cursor() as $r) {
                $targets = $a->anchor === 'meeting'
                    ? $r->session->meetings->filter(fn ($m) => $m->starts_at->between($from, $to))->pluck('id')->all()
                    : [0];
                foreach ($targets as $mid) foreach ($a->channels as $ch) {
                    $skip = ($ch === 'mail' && !$r->email);
                    $log = MessageLog::firstOrCreate(
                        ['registration_id' => $r->id, 'automation_id' => $a->id, 'meeting_id' => $mid, 'channel' => $ch],
                        ['status' => $skip ? 'skipped' : 'queued']
                    );
                    if ($log->wasRecentlyCreated && !$skip) SendMessage::dispatch($log->id);
                }
            }
        }
    }
}
