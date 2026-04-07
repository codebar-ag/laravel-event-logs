<?php

namespace CodebarAg\LaravelEventLogs\Models;

use Carbon\Carbon;
use CodebarAg\LaravelEventLogs\Database\Factories\EventLogFactory;
use CodebarAg\LaravelEventLogs\Enums\EventLogEventEnum;
use CodebarAg\LaravelEventLogs\Enums\EventLogTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

/**
 * @property int $id
 * @property string $uuid
 * @property EventLogTypeEnum|null $type
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property string|null $user_type
 * @property int|null $user_id
 * @property string|null $request_ip
 * @property string|null $request_method
 * @property string|null $request_url
 * @property string|null $request_route
 * @property int|null $response_status
 * @property int|null $duration_ms
 * @property array<string, mixed>|null $request_headers
 * @property array<string, mixed>|null $request_data
 * @property EventLogEventEnum|null $event
 * @property array<string, mixed>|null $event_data
 * @property array<string, mixed>|null $context
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @use HasFactory<EventLogFactory>
 */
class EventLog extends Model
{
    /**
     * @use HasFactory<EventLogFactory>
     */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'type',
        'subject_type',
        'subject_id',
        'user_type',
        'user_id',
        'request_route',
        'response_status',
        'duration_ms',
        'request_method',
        'request_url',
        'request_ip',
        'request_headers',
        'request_data',
        'event',
        'event_data',
        'context',
    ];

    /**
     * Get the database connection name for the model.
     */
    public function getConnectionName(): ?string
    {
        $connection = Config::get('laravel-event-logs.connection');

        if (! empty($connection) && is_string($connection)) {
            return $connection;
        }

        return parent::getConnectionName();
    }

    /**
     * Check if event logs are enabled and properly configured.
     */
    public static function isEnabled(): bool
    {
        $enabled = (bool) Config::get('laravel-event-logs.enabled', false);
        if (! $enabled) {
            return false;
        }

        $connection = Config::get('laravel-event-logs.connection');
        if (empty($connection)) {
            return false;
        }

        return true;
    }

    /** @var array<string, string> */
    protected $casts = [
        'request_headers' => 'array',
        'request_data' => 'array',
        'type' => EventLogTypeEnum::class,
        'event' => EventLogEventEnum::class,
        'event_data' => 'array',
        'context' => 'array',
        'response_status' => 'integer',
        'duration_ms' => 'integer',
        'subject_id' => 'string',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): EventLogFactory
    {
        return EventLogFactory::new();
    }
}
