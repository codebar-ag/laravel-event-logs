<?php

namespace CodebarAg\LaravelEventLogs\Jobs;

use CodebarAg\LaravelEventLogs\Models\EventLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordEventLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload) {}

    public function handle(): void
    {
        if (! EventLog::isEnabled()) {
            return;
        }

        EventLog::create($this->payload);
    }
}
