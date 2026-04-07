<?php

namespace CodebarAg\LaravelEventLogs\Actions;

use CodebarAg\LaravelEventLogs\Transports\AzureEventHubTransport;

/**
 * @deprecated Use {@see AzureEventHubTransport} or {@see EventLogTransport}
 */
class AzureEventHubAction extends AzureEventHubTransport {}
