<?php

use CodebarAg\LaravelEventLogs\Middleware\EventLogMiddleware;
use CodebarAg\LaravelEventLogs\Models\EventLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;

test('middleware logs request when enabled', function () {
    $route = Mockery::mock(Route::class);
    $route->shouldReceive('getName')->andReturn('api.users.store');

    $request = Request::create('https://example.com/api/users', 'POST', ['name' => 'John Doe']);
    $request->setRouteResolver(function () use ($route) {
        return $route;
    });
    $request->server->set('REMOTE_ADDR', '127.0.0.1');
    $request->headers->set('Content-Type', 'application/json');

    Auth::shouldReceive('user')->andReturn(null);
    Auth::shouldReceive('id')->andReturn(null);

    config()->set('laravel-event-logs.enabled', true);
    config()->set('laravel-event-logs.exclude_routes', []);
    config()->set('laravel-event-logs.sanitize.request_headers_exclude', []);
    config()->set('laravel-event-logs.sanitize.request_data_exclude', []);

    $middleware = new EventLogMiddleware;
    $response = new Response('OK', 200);

    $result = $middleware->handle($request, function ($req) use ($response) {
        return $response;
    });

    expect($result)->toBe($response);

    $eventLog = EventLog::where('request_route', 'api.users.store')->first();
    expect($eventLog)->not->toBeNull();
    expect($eventLog->request_method)->toBe('POST');
    expect($eventLog->request_url)->toBe('https://example.com/api/users');
    expect($eventLog->request_ip)->toBe('127.0.0.1');
});

test('middleware skips logging when disabled', function () {
    config()->set('laravel-event-logs.enabled', false);

    $request = Request::create('https://example.com/api/users', 'GET');
    $middleware = new EventLogMiddleware;
    $response = new Response('OK', 200);

    $result = $middleware->handle($request, function ($req) use ($response) {
        return $response;
    });

    expect($result)->toBe($response);

    // Check that no event log was created
    $eventLogCount = EventLog::count();
    expect($eventLogCount)->toBe(0);
});

test('middleware skips excluded routes', function () {
    $route = Mockery::mock(Route::class);
    $route->shouldReceive('getName')->andReturn('api.users.store');

    $request = Request::create('https://example.com/api/users', 'POST');
    $request->setRouteResolver(function () use ($route) {
        return $route;
    });
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    Auth::shouldReceive('user')->andReturn(null);
    Auth::shouldReceive('id')->andReturn(null);

    config()->set('laravel-event-logs.enabled', true);
    config()->set('laravel-event-logs.exclude_routes', ['api.users.store']);
    config()->set('laravel-event-logs.sanitize.request_headers_exclude', []);
    config()->set('laravel-event-logs.sanitize.request_data_exclude', []);

    $middleware = new EventLogMiddleware;
    $response = new Response('OK', 200);

    $result = $middleware->handle($request, function ($req) use ($response) {
        return $response;
    });

    expect($result)->toBe($response);

    $eventLogCount = EventLog::count();
    expect($eventLogCount)->toBe(0);
});

test('middleware handles route without name', function () {
    $route = Mockery::mock(Route::class);
    $route->shouldReceive('getName')->andReturn(null);

    $request = Request::create('https://example.com/api/users', 'GET');
    $request->setRouteResolver(function () use ($route) {
        return $route;
    });
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    Auth::shouldReceive('user')->andReturn(null);
    Auth::shouldReceive('id')->andReturn(null);

    config()->set('laravel-event-logs.enabled', true);
    config()->set('laravel-event-logs.exclude_routes', []);
    config()->set('laravel-event-logs.sanitize.request_headers_exclude', []);
    config()->set('laravel-event-logs.sanitize.request_data_exclude', []);

    $middleware = new EventLogMiddleware;
    $response = new Response('OK', 200);

    $result = $middleware->handle($request, function ($req) use ($response) {
        return $response;
    });

    expect($result)->toBe($response);

    $eventLog = EventLog::where('request_route', null)->first();
    expect($eventLog)->not->toBeNull();
});

test('middleware handles null route', function () {
    $request = Request::create('https://example.com/api/users', 'GET');
    $request->setRouteResolver(function () {
        return null;
    });
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    Auth::shouldReceive('user')->andReturn(null);
    Auth::shouldReceive('id')->andReturn(null);

    config()->set('laravel-event-logs.enabled', true);
    config()->set('laravel-event-logs.exclude_routes', []);
    config()->set('laravel-event-logs.sanitize.request_headers_exclude', []);
    config()->set('laravel-event-logs.sanitize.request_data_exclude', []);

    $middleware = new EventLogMiddleware;
    $response = new Response('OK', 200);

    $result = $middleware->handle($request, function ($req) use ($response) {
        return $response;
    });

    expect($result)->toBe($response);

    $eventLog = EventLog::where('request_route', null)->first();
    expect($eventLog)->not->toBeNull();
});
