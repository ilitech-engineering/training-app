<?php
namespace App\Http\Controllers;
use App\Models\{Course, Registration};
use App\Services\SlickPay;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function store(Request $req, Course $course, SlickPay $pay)
    {
        $d = $req->validate([
            'name' => 'required|string|min:3|max:120', 'phone' => ['required', 'regex:/^[0-9+\s]{9,15}$/'], 'email' => 'nullable|email',
            'course_session_id' => 'required|integer', 'method' => 'nullable|in:cib,ccp', 'addons' => 'nullable|array', 'consent' => 'nullable|boolean', 'lang' => 'nullable|in:ar,fr,en',
        ]);
        $loc = $d['lang'] ?? 'ar'; app()->setLocale($loc);
        $s = $course->sessions()->where('status', 'open')->where('starts_at', '>', now())->findOrFail($d['course_session_id']);

        $reg = Registration::where('course_session_id', $s->id)->where('phone', $d['phone'])->where('status', '!=', 'cancelled')->first();
        if ($reg?->status === 'confirmed') return redirect()->route('thanks', $reg);
        if (!$reg && $s->seatsLeft() < 1) return back()->withErrors(['course_session_id' => __('ui.full')])->withInput();

        // The amount is always computed here, never taken from the browser.
        $price = $s->currentPrice();
        $addons = collect($course->addons ?? [])->whereIn('key', $d['addons'] ?? []);
        $amount = $price > 0 ? $price + $addons->sum('price') : 0;
        if ($amount > 0 && empty($d['method'])) return back()->withErrors(['method' => __('ui.y5')])->withInput();

        $reg ??= new Registration();
        $reg->fill([
            'course_id' => $course->id, 'course_session_id' => $s->id, 'name' => $d['name'], 'phone' => $d['phone'], 'email' => $d['email'] ?? null,
            'locale' => $loc, 'amount' => $amount, 'addons' => $addons->pluck('key')->values()->all(), 'consent' => (bool) ($d['consent'] ?? false),
            'method' => $amount ? $d['method'] : 'free', 'status' => $amount ? 'pending' : 'confirmed',
        ])->save();

        if (!$amount) return redirect()->route('thanks', $reg);
        if ($reg->method === 'ccp') return redirect()->route('ccp', $reg);
        try {
            $inv = $pay->invoice($reg);
            $reg->update(['invoice_id' => $inv['id']]);
            return redirect()->away($inv['url']);
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['method' => __('ui.err1')])->withInput();
        }
    }

    public function ccp(Request $r, Registration $registration)
    {
        CourseController::locale($r);
        abort_if($registration->status === 'confirmed', 404);
        return view('ccp', ['reg' => $registration->load('course', 'session')]);
    }

    public function thanks(Request $r, Registration $registration)
    {
        CourseController::locale($r);
        return view('thanks', ['reg' => $registration->load('course', 'session.meetings')]);
    }

    public function ics(Registration $registration)
    {
        abort_unless($registration->status === 'confirmed', 404);
        $out = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//ILITECH TRAINING//EN\r\n";
        foreach ($registration->session->meetings as $m) {
            $out .= "BEGIN:VEVENT\r\nUID:{$registration->uuid}-{$m->n}@ilitech\r\nDTSTAMP:" . now()->utc()->format('Ymd\THis\Z') . "\r\nDTSTART:" . $m->starts_at->copy()->utc()->format('Ymd\THis\Z')
                . "\r\nDTEND:" . $m->starts_at->copy()->addHours(2)->utc()->format('Ymd\THis\Z') . "\r\nSUMMARY:" . $registration->course->name('fr') . " #{$m->n}\r\nLOCATION:" . ($registration->session->location ?: 'Online') . "\r\nEND:VEVENT\r\n";
        }
        return response($out . "END:VCALENDAR\r\n", 200, ['Content-Type' => 'text/calendar', 'Content-Disposition' => 'attachment; filename=training.ics']);
    }
}
