<?php

namespace CodebarAg\LaravelEventLogs\Database\Factories;

use CodebarAg\LaravelEventLogs\Enums\EventLogEventEnum;
use CodebarAg\LaravelEventLogs\Enums\EventLogTypeEnum;
use CodebarAg\LaravelEventLogs\Models\EventLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventLog>
 */
class EventLogFactory extends Factory
{
    protected $model = EventLog::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) fake()->uuid(),
            'type' => fake()->randomElement(EventLogTypeEnum::cases()),
            'subject_type' => 'App\Models\User',
            'subject_id' => fake()->numberBetween(1, 1000),
            'user_type' => 'App\Models\User',
            'user_id' => fake()->numberBetween(1, 1000),
            'request_route' => 'tests.route',
            'request_method' => fake()->randomElement(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD']),
            'request_url' => 'https://example.test',
            'request_ip' => fake()->ipv4(),
            'request_headers' => ['accept' => ['application/json']],
            'request_data' => ['a' => 1],
            'event' => fake()->randomElement(EventLogEventEnum::cases()),
            'event_data' => ['k' => 'v'],
            'context' => ['k' => 'v'],
        ];
    }
}
