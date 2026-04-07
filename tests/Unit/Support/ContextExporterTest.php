<?php

use CodebarAg\LaravelEventLogs\Support\ContextExporter;
use Illuminate\Support\Facades\Context;

test('context exporter returns empty when disabled', function () {
    config()->set('laravel-event-logs.context.enabled', false);

    Context::add('trace_id', 'abc');

    expect(ContextExporter::forPersistence())->toBe([]);
});

test('context exporter allowlist filters keys', function () {
    config()->set('laravel-event-logs.context.enabled', true);
    config()->set('laravel-event-logs.context.allow_keys', ['keep']);
    config()->set('laravel-event-logs.context.max_keys', null);
    config()->set('laravel-event-logs.context.max_json_bytes', null);

    Context::flush();
    Context::add('keep', 'yes');
    Context::add('drop', 'no');

    expect(ContextExporter::forPersistence())->toBe(['keep' => 'yes']);
});

test('context exporter max keys truncates', function () {
    config()->set('laravel-event-logs.context.enabled', true);
    config()->set('laravel-event-logs.context.allow_keys', []);
    config()->set('laravel-event-logs.context.max_keys', 1);
    config()->set('laravel-event-logs.context.max_json_bytes', null);

    Context::flush();
    Context::add('a', 1);
    Context::add('b', 2);

    $out = ContextExporter::forPersistence();
    expect(count($out))->toBe(1);
});
