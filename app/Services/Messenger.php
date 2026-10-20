<?php
namespace App\Services;
use App\Models\{MessageLog, Meeting, Registration};
use Illuminate\Support\Facades\{Http, Mail};

class Messenger
{
    public static function dz(string $p): string   // 05xx xx xx xx -> 2135xxxxxxxx
    {
        $p = preg_replace('/\D+/', '', $p);
        if (str_starts_with($p, '00')) $p = substr($p, 2);
        return str_starts_with($p, '0') ? '213' . substr($p, 1) : $p;
    }

    /** Fills {name} {course} {date} {time} {location} {link} {page} {promo} in the registrant's language. */
    public function render(MessageLog $log): array
    {
        $r = $log->registration; $a = $log->automation; $l = $r->locale; $s = $r->session;
        $m = $log->meeting_id ? Meeting::find($log->meeting_id) : null;
        $d = ($m?->starts_at ?? $s->starts_at)->copy()->locale($l);
        $vars = [
            '{name}' => $r->name, '{course}' => $r->course->name($l),
            '{date}' => $d->translatedFormat('l d F Y'), '{time}' => $d->format('H:i'),
            '{location}' => $s->location ?: '—', '{link}' => $m?->meeting_url ?: ($s->meeting_url ?: '—'),
            '{page}' => route('course', $r->course->slug), '{promo}' => $a->promote ? route('course', $a->promote->slug) : '',
            '{ticket}' => route('thanks', $r),
        ];
        return [strtr($a->subject[$l] ?? $a->subject['fr'] ?? $r->course->name($l), $vars), strtr($a->body[$l] ?? $a->body['fr'] ?? '', $vars)];
    }

    public function send(string $channel, Registration $r, string $subject, string $body): void
    {
        match ($channel) {
            'mail' => Mail::raw($body, fn ($m) => $m->to($r->email)->subject($subject)),
            // WhatsApp through a self-hosted WAHA gateway (https://waha.devlike.pro). Swap for Meta Cloud API if you prefer.
            'whatsapp' => Http::withHeaders(['X-Api-Key' => (string) config('training.waha.key')])->timeout(20)
                ->post(rtrim(config('training.waha.url'), '/') . '/api/sendText', [
                    'session' => config('training.waha.session'), 'chatId' => self::dz($r->phone) . '@c.us', 'text' => $body,
                ])->throw(),
            // Generic SMS HTTP gateway: adapt field names to your provider's API.
            'sms' => Http::withToken((string) config('training.sms.token'))->timeout(20)
                ->post(config('training.sms.url'), ['to' => self::dz($r->phone), 'from' => config('training.sms.from'), 'message' => $body])->throw(),
        };
    }
}
