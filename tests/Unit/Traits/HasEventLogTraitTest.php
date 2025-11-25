<?php

use CodebarAg\LaravelEventLogs\Enums\EventLogEventEnum;
use CodebarAg\LaravelEventLogs\Enums\EventLogTypeEnum;
use CodebarAg\LaravelEventLogs\Models\EventLog;
use CodebarAg\LaravelEventLogs\Traits\HasEventLogTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TestModel extends Model
{
    use HasEventLogTrait;

    protected $table = 'test_models';

    protected $fillable = ['name', 'email'];
}

test('trait can log created event', function () {
    Auth::shouldReceive('user')->andReturn(null);
    Auth::shouldReceive('id')->andReturn(null);

    $model = new TestModel;
    $model->name = 'John Doe';
    $model->email = 'john@example.com';

    $model->save();

    $eventLog = EventLog::where('subject_type', TestModel::class)->first();
    expect($eventLog)->not->toBeNull();
    expect($eventLog->type)->toBe(EventLogTypeEnum::MODEL);
    expect($eventLog->event)->toBe(EventLogEventEnum::CREATED);
    expect($eventLog->subject_id)->toBe($model->id);
});

test('trait can log updated event', function () {
    Auth::shouldReceive('user')->andReturn(null);
    Auth::shouldReceive('id')->andReturn(null);

    $model = new TestModel;
    $model->name = 'John Doe';
    $model->email = 'john@example.com';
    $model->save();

    $model->name = 'Jane Doe';
    $model->save();

    $eventLog = EventLog::where('subject_type', TestModel::class)
        ->where('type', EventLogTypeEnum::MODEL)
        ->where('event', EventLogEventEnum::UPDATED)
        ->first();
    expect($eventLog)->not->toBeNull();
    expect($eventLog->event)->toBe(EventLogEventEnum::UPDATED);
    expect($eventLog->subject_id)->toBe($model->id);
});

test('trait can log deleted event', function () {
    Auth::shouldReceive('user')->andReturn(null);
    Auth::shouldReceive('id')->andReturn(null);

    $model = new TestModel;
    $model->name = 'John Doe';
    $model->email = 'john@example.com';
    $model->save();

    $model->delete();

    $eventLog = EventLog::where('subject_type', TestModel::class)
        ->where('type', EventLogTypeEnum::MODEL)
        ->where('event', EventLogEventEnum::DELETED)
        ->first();
    expect($eventLog)->not->toBeNull();
    expect($eventLog->event)->toBe(EventLogEventEnum::DELETED);
    expect($eventLog->subject_id)->toBe($model->id);
});

test('trait can log restored event', function () {
    Auth::shouldReceive('user')->andReturn(null);
    Auth::shouldReceive('id')->andReturn(null);

    $model = new class extends TestModel
    {
        use Illuminate\Database\Eloquent\SoftDeletes;
    };
    $model->name = 'John Doe';
    $model->email = 'john@example.com';
    $model->save();

    $model->delete();
    $model->restore();

    $eventLog = EventLog::where('subject_type', get_class($model))
        ->where('type', EventLogTypeEnum::MODEL)
        ->where('event', EventLogEventEnum::RESTORED)
        ->first();
    expect($eventLog)->not->toBeNull();
    expect($eventLog->event)->toBe(EventLogEventEnum::RESTORED);
    expect($eventLog->subject_id)->toBe($model->id);
});

test('trait logs user information when authenticated', function () {
    $user = Mockery::mock('App\Models\User');
    $user->shouldReceive('getKey')->andReturn(1);
    $user->id = 1;

    Auth::shouldReceive('user')->andReturn($user);
    Auth::shouldReceive('id')->andReturn(1);

    $model = new TestModel;
    $model->name = 'John Doe';
    $model->email = 'john@example.com';

    $model->save();

    $eventLog = EventLog::where('subject_type', TestModel::class)
        ->where('type', EventLogTypeEnum::MODEL)
        ->where('event', EventLogEventEnum::CREATED)
        ->first();
    expect($eventLog)->not->toBeNull();
    expect($eventLog->user_type)->toContain('App_Models_User');
    expect($eventLog->user_id)->toBe(1);
});

test('trait handles model without id', function () {
    Auth::shouldReceive('user')->andReturn(null);
    Auth::shouldReceive('id')->andReturn(null);

    $model = new TestModel;
    $model->name = 'John Doe';
    $model->email = 'john@example.com';

    $model->save();

    $eventLog = EventLog::where('subject_type', TestModel::class)
        ->where('type', EventLogTypeEnum::MODEL)
        ->where('event', EventLogEventEnum::CREATED)
        ->first();
    expect($eventLog)->not->toBeNull();
    expect($eventLog->subject_id)->toBe($model->id);
    expect($eventLog->user_type)->toBeNull();
    expect($eventLog->user_id)->toBeNull();
});

test('trait skips logging when disabled', function () {
    config()->set('laravel-event-logs.enabled', false);

    $model = new TestModel;
    $model->name = 'Disabled Case';
    $model->email = 'disabled@example.com';
    $model->save();

    expect(EventLog::count())->toBe(0);

    config()->set('laravel-event-logs.enabled', true);
});

test('trait logs event data with correct structure for created event', function () {
    Auth::shouldReceive('user')->andReturn(null);
    Auth::shouldReceive('id')->andReturn(null);

    $model = new TestModel;
    $model->name = 'John Doe';
    $model->email = 'john@example.com';
    $model->save();

    $eventLog = EventLog::where('subject_type', TestModel::class)
        ->where('event', EventLogEventEnum::CREATED)
        ->first();

    expect($eventLog)->not->toBeNull();
    expect($eventLog->event_data)->toBeArray();
    expect($eventLog->event_data['event'])->toBe('created');
    expect($eventLog->event_data['model_type'])->toBe(TestModel::class);
    expect($eventLog->event_data['model_id'])->toBe($model->id);
    expect($eventLog->event_data)->toHaveKey('attributes');
    expect($eventLog->event_data)->toHaveKey('changes');
    expect($eventLog->event_data)->toHaveKey('original');
});

test('trait logs event data with correct structure for updated event', function () {
    Auth::shouldReceive('user')->andReturn(null);
    Auth::shouldReceive('id')->andReturn(null);

    $model = new TestModel;
    $model->name = 'John Doe';
    $model->email = 'john@example.com';
    $model->save();

    $model->name = 'Jane Doe';
    $model->save();

    $eventLog = EventLog::where('subject_type', TestModel::class)
        ->where('event', EventLogEventEnum::UPDATED)
        ->first();

    expect($eventLog)->not->toBeNull();
    expect($eventLog->event_data)->toBeArray();
    expect($eventLog->event_data['event'])->toBe('updated');
    expect($eventLog->event_data['model_type'])->toBe(TestModel::class);
    expect($eventLog->event_data['model_id'])->toBe($model->id);
    expect($eventLog->event_data)->toHaveKey('attributes');
    expect($eventLog->event_data)->toHaveKey('changes');
    expect($eventLog->event_data)->toHaveKey('original');
    expect($eventLog->event_data)->toHaveKey('dirty_keys');
    expect($eventLog->event_data['dirty_keys'])->toContain('name');
});

test('trait sanitizes hidden attributes from event data', function () {
    Auth::shouldReceive('user')->andReturn(null);
    Auth::shouldReceive('id')->andReturn(null);

    $model = new class extends TestModel
    {
        protected $hidden = ['email'];
    };
    $model->name = 'John Doe';
    $model->email = 'hidden@example.com';
    $model->save();

    $eventLog = EventLog::where('subject_type', get_class($model))
        ->where('event', EventLogEventEnum::CREATED)
        ->first();

    expect($eventLog)->not->toBeNull();
    expect($eventLog->event_data['attributes'])->not->toHaveKey('email');
    expect($eventLog->event_data['attributes'])->toHaveKey('name');
});
