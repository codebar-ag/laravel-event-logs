<?php

namespace CodebarAg\LaravelEventLogs\DTO;

use CodebarAg\LaravelEventLogs\Enums\EventLogEventEnum;
use CodebarAg\LaravelEventLogs\Enums\EventLogTypeEnum;
use CodebarAg\LaravelEventLogs\Models\EventLog;

class AzureEventHubDTO
{
    public function __construct(
        public readonly string $uuid,
        public readonly ?EventLogTypeEnum $type,
        public readonly ?string $subject_type,
        public readonly ?string $subject_id,
        public readonly ?string $user_type,
        public readonly ?int $user_id,
        public readonly ?string $request_route,
        public readonly ?int $response_status,
        public readonly ?int $duration_ms,
        public readonly ?string $request_method,
        public readonly ?string $request_url,
        public readonly ?string $request_ip,
        /** @var array<string, mixed>|null */
        public readonly ?array $request_headers,
        /** @var array<string, mixed>|null */
        public readonly ?array $request_data,
        public readonly ?EventLogEventEnum $event,
        /** @var array<string, mixed>|null */
        public readonly ?array $event_data,
        /** @var array<string, mixed>|null */
        public readonly ?array $context,
        public readonly ?string $created_at,
    ) {}

    public static function fromEventLog(EventLog $eventLog): self
    {
        return new self(
            uuid: (string) $eventLog->uuid,
            type: $eventLog->type instanceof EventLogTypeEnum ? $eventLog->type : null,
            subject_type: $eventLog->subject_type,
            subject_id: $eventLog->subject_id !== null && $eventLog->subject_id !== ''
                ? (string) $eventLog->subject_id
                : null,
            user_type: $eventLog->user_type,
            user_id: $eventLog->user_id,
            request_route: $eventLog->request_route,
            response_status: $eventLog->response_status !== null ? (int) $eventLog->response_status : null,
            duration_ms: $eventLog->duration_ms !== null ? (int) $eventLog->duration_ms : null,
            request_method: $eventLog->request_method,
            request_url: $eventLog->request_url,
            request_ip: $eventLog->request_ip,
            request_headers: $eventLog->request_headers,
            request_data: $eventLog->request_data,
            event: $eventLog->event instanceof EventLogEventEnum ? $eventLog->event : null,
            event_data: $eventLog->event_data,
            context: $eventLog->context,
            created_at: $eventLog->created_at->toIso8601String(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type?->value,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'user_type' => $this->user_type,
            'user_id' => $this->user_id,
            'request_route' => $this->request_route,
            'response_status' => $this->response_status,
            'duration_ms' => $this->duration_ms,
            'request_method' => $this->request_method,
            'request_url' => $this->request_url,
            'request_ip' => $this->request_ip,
            'request_headers' => $this->request_headers,
            'request_data' => $this->request_data,
            'event' => $this->event?->value,
            'event_data' => $this->event_data,
            'context' => $this->context,
            'created_at' => $this->created_at,
        ];
    }
}
