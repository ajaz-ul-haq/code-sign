<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodeSignElement extends Model
{
    protected $table = 'code_signed_elements';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array'
    ];

    public function payloadValue($key)
    {
        return $this->payload[$key] ?? '';
    }

    public function isSuccessFull($reason = null): void
    {
        $this->updateStatus(1, $reason);
    }

    public function markAsFailed($reason): void
    {
       $this->updateStatus(0, $reason);
    }

    private function updateStatus(int $status, $reason): void
    {
        $this->update(['status' => $status, 'comment' => $reason]);
    }

    public function isAcknowledged(): bool
    {
        return $this->getRawOriginal('sent');
    }

    public function getWebhookUrl(): string
    {
        return rtrim($this->payloadValue('apiUrl'), '/').'/v3/agent-software/webhook';
    }

    public function isForScript(): bool
    {
        return $this->type === 'windows_script';
    }

    public function isForAgent(): bool
    {
        return $this->type === 'agent';
    }

    public function finalize()
    {
        $this->update(['sent' => 1]);
    }

    public function needsCompilation(): bool
    {
        return ($this->payloadValue('platform') === 'windows') && $this->status === 0;
    }

}
