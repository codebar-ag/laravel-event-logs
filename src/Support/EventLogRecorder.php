<?php

namespace CodebarAg\LaravelEventLogs\Support;

use CodebarAg\LaravelEventLogs\Jobs\RecordEventLogJob;
use CodebarAg\LaravelEventLogs\Models\EventLog;

final class EventLogRecorder
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function record(array $attributes): void
    {
        if (! EventLog::isEnabled()) {
            return;
        }

        $mode = config('laravel-event-logs.persist_mode', 'sync');
        if ($mode === 'queued') {
            $job = new RecordEventLogJob($attributes);
            $connection = config('laravel-event-logs.queue.connection');
            $queue = config('laravel-event-logs.queue.queue');
            if (is_string($connection) && $connection !== '') {
                $job->onConnection($connection);
            }
            if (is_string($queue) && $queue !== '') {
                $job->onQueue($queue);
            }
            dispatch($job);

            return;
        }

        EventLog::create($attributes);
    }
}
