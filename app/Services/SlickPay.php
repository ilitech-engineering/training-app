<?php
namespace App\Services;
use App\Models\Registration;
use Illuminate\Support\Facades\Http;

class SlickPay
{
    private function http() { return Http::withToken(config('training.slickpay.key'))->acceptJson()->baseUrl(config('training.slickpay.base'))->timeout(20); }

    /** Creates an invoice and returns the SATIM payment URL (CIB / Edahabia). */
    public function invoice(Registration $r): array
    {
        [$first, $last] = array_pad(explode(' ', trim($r->name), 2), 2, null);
        $res = $this->http()->post('/users/invoices', array_filter([
            'amount' => $r->amount,                    // computed server-side only, must be > 100
            'fees' => 100,                             // 100 = the client pays the commission, 0 = you do
            'account' => config('training.slickpay.account'),
            'url' => route('slickpay.return', $r),
            'items' => [['name' => $r->course->name('fr'), 'price' => $r->amount, 'quantity' => 1]],
            'firstname' => $first, 'lastname' => $last ?: $first, 'phone' => $r->phone,
            'email' => $r->email ?: 'no-reply@ilitech-engineering.dz', 'address' => 'Algérie',
            'webhook_url' => route('slickpay.webhook'), 'webhook_signature' => config('training.slickpay.secret'),
            'webhook_meta_data' => ['registration' => $r->uuid],
        ]))->throw()->json();
        return ['id' => (string) $res['id'], 'url' => $res['url']];   // root `url` = SATIM page (not invoice.url)
    }

    public function paid(string $invoiceId): bool
    {
        return (int) $this->http()->get("/users/invoices/{$invoiceId}")->throw()->json('completed') === 1;
    }
}
