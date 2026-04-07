<?php

use CodebarAg\LaravelEventLogs\Support\RouteExclusion;

test('exact mode matches only full string', function () {
    expect(RouteExclusion::shouldExclude('api.users.store', ['api.users.store'], 'exact'))->toBeTrue();
    expect(RouteExclusion::shouldExclude('api.users.index', ['api.users.store'], 'exact'))->toBeFalse();
});

test('wildcard mode uses Str is', function () {
    expect(RouteExclusion::shouldExclude('nova.api.users', ['nova.api.*'], 'wildcard'))->toBeTrue();
    expect(RouteExclusion::shouldExclude('nova.pages.home', ['nova.api.*'], 'wildcard'))->toBeFalse();
});

test('auto mode treats trailing dot as prefix', function () {
    expect(RouteExclusion::shouldExclude('nova.api.foo', ['nova.api.'], 'auto'))->toBeTrue();
    expect(RouteExclusion::shouldExclude('nova.pages.home', ['nova.api.'], 'auto'))->toBeFalse();
});

test('auto mode uses glob when pattern contains asterisk', function () {
    expect(RouteExclusion::shouldExclude('livewire.update', ['livewire.*'], 'auto'))->toBeTrue();
});

test('null route name is never excluded', function () {
    expect(RouteExclusion::shouldExclude(null, ['any'], 'exact'))->toBeFalse();
});
