<?php

use CodebarAg\LaravelEventLogs\Support\RouteExclusion;

test('exact mode matches full route name only', function () {
    expect(RouteExclusion::shouldExclude('api.users.store', ['api.users.store'], 'exact'))->toBeTrue();
    expect(RouteExclusion::shouldExclude('api.users.index', ['api.users.store'], 'exact'))->toBeFalse();
    expect(RouteExclusion::shouldExclude('nova.pages.home', ['nova.'], 'exact'))->toBeFalse();
});

test('auto mode uses prefix when pattern ends with dot', function () {
    expect(RouteExclusion::shouldExclude('nova.pages.home', ['nova.'], 'auto'))->toBeTrue();
    expect(RouteExclusion::shouldExclude('livewire.update', ['livewire.'], 'auto'))->toBeTrue();
    expect(RouteExclusion::shouldExclude('livewire-filepond.scripts', ['livewire-filepond.'], 'auto'))->toBeTrue();
    expect(RouteExclusion::shouldExclude('app.dashboard', ['nova.'], 'auto'))->toBeFalse();
});

test('auto mode uses Str is when pattern contains asterisk', function () {
    expect(RouteExclusion::shouldExclude('nova.pages.home', ['nova.*'], 'auto'))->toBeTrue();
    expect(RouteExclusion::shouldExclude('other.route', ['nova.*'], 'auto'))->toBeFalse();
});

test('auto mode falls back to exact when no glob or prefix rule applies', function () {
    expect(RouteExclusion::shouldExclude('api.users.store', ['api.users.store'], 'auto'))->toBeTrue();
    expect(RouteExclusion::shouldExclude('api.users.index', ['api.users.store'], 'auto'))->toBeFalse();
});

test('wildcard mode applies Str is to every pattern', function () {
    expect(RouteExclusion::shouldExclude('nova.pages.home', ['nova.*'], 'wildcard'))->toBeTrue();
    expect(RouteExclusion::shouldExclude('nova.pages.home', ['nova.'], 'wildcard'))->toBeFalse();
});

test('shouldExclude returns false for empty route name or patterns', function () {
    expect(RouteExclusion::shouldExclude(null, ['nova.'], 'auto'))->toBeFalse();
    expect(RouteExclusion::shouldExclude('', ['nova.'], 'auto'))->toBeFalse();
    expect(RouteExclusion::shouldExclude('nova.pages.home', [], 'auto'))->toBeFalse();
});
