<?php
namespace App\Jobs;
use App\Models\MessageLog;
use App\Services\Messenger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendMessage implements ShouldQueue
{
    use Queueable;
    public int $tries = 3;
    public array $backoff = [60, 300];
    public function __construct(public int $logId) {}

    public function handle(Messenger $m): void
    {
        $log = MessageLog::with(['registration.course', 'registration.session', 'automation.promote'])->find($this->logId);
        if (!$log || $log->status === 'sent') return;
        try {
            [$subject, $body] = $m->render($log);
            $m->send($log->channel, $log->registration, $subject, $body);
            $log->update(['status' => 'sent', 'error' => null]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500)]);
            throw $e;
        }
    }
}
