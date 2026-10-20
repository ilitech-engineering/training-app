<?php
namespace App\Http\Controllers;
use App\Models\Registration;
use App\Services\SlickPay;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /** The buyer comes back from SATIM: ask SlickPay (server to server) whether it was really paid. */
    public function back(Registration $registration, SlickPay $pay)
    {
        if ($registration->status === 'pending' && $registration->invoice_id) {
            try { if ($pay->paid($registration->invoice_id)) $registration->markPaid(); } catch (\Throwable $e) { report($e); }
        }
        return redirect()->route('thanks', $registration);
    }

    /** Webhook: we never trust the payload, we re-check the invoice with the API. */
    public function webhook(Request $r, SlickPay $pay)
    {
        $id = (string) ($r->input('id') ?? $r->input('data.id') ?? '');
        $reg = $id ? Registration::where('invoice_id', $id)->first() : null;
        if ($reg && $reg->status === 'pending' && $pay->paid($id)) $reg->markPaid();
        return response()->json(['ok' => 1]);
    }
}
