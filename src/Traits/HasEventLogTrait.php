<?php

namespace CodebarAg\LaravelEventLogs\Traits;

use CodebarAg\LaravelEventLogs\Enums\EventLogEventEnum;
use CodebarAg\LaravelEventLogs\Enums\EventLogTypeEnum;
use CodebarAg\LaravelEventLogs\Models\EventLog;
use CodebarAg\LaravelEventLogs\Support\SanitizeHelper;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;

trait HasEventLogTrait
{
    protected static function bootHasEventLogTrait(): void
    {
        if (! EventLog::isEnabled()) {
            return;
        }

        static::created(fn ($model) => $model->logModelEvent(EventLogEventEnum::CREATED));
        static::updated(fn ($model) => $model->logModelEvent(EventLogEventEnum::UPDATED));
        static::deleted(fn ($model) => $model->logModelEvent(EventLogEventEnum::DELETED));

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            static::restored(fn ($model) => $model->logModelEvent(EventLogEventEnum::RESTORED));
        }
    }

    protected function logModelEvent(EventLogEventEnum $event): void
    {
        if (! EventLog::isEnabled()) {
            return;
        }

        $user = Auth::user();

        $attributes = $event === EventLogEventEnum::CREATED
            ? (array) $this->getAttributes()
            : [];
        $changes = $event === EventLogEventEnum::UPDATED
            ? (array) $this->getChanges()
            : [];
        $original = $event === EventLogEventEnum::UPDATED
            ? (array) $this->getOriginal()
            : [];

        $payload = [
            'event' => $event->value,
            'model_type' => static::class,
            'model_id' => $this->getKey(),
            'attributes' => SanitizeHelper::removeKeys($attributes, $this->getHidden()),
            'changes' => SanitizeHelper::removeKeys($changes, $this->getHidden()),
            'original' => SanitizeHelper::removeKeys($original, $this->getHidden()),
            'dirty_keys' => array_keys($changes),
        ];

        EventLog::create([
            'type' => EventLogTypeEnum::MODEL->value,
            'subject_type' => static::class,
            'subject_id' => (string) $this->getKey(),
            'user_type' => $user ? get_class($user) : null,
            'user_id' => $user?->id,
            'event' => $event->value,
            'event_data' => $payload,
            'context' => Context::all(),
        ]);
    }
}
