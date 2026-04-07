<?php

namespace CodebarAg\LaravelEventLogs\Contracts;

use CodebarAg\LaravelEventLogs\Models\EventLog;
use Illuminate\Http\Client\Response;

interface EventLogTransport
{
    public function send(EventLog $eventLog): Response;
}
