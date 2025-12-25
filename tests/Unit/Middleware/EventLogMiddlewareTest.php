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

test('middleware sanitizes request headers', function () {
    $route = Mockery::mock(Route::class);
    $route->shouldReceive('getName')->andReturn('api.users.store');

    $request = Request::create('https://example.com/api/users', 'POST');
    $request->setRouteResolver(function () use ($route) {
        return $route;
    });
    $request->server->set('REMOTE_ADDR', '127.0.0.1');
    $request->headers->set('Authorization', 'Bearer token123');
    $request->headers->set('Content-Type', 'application/json');
    $request->headers->set('Cookie', 'session=abc123');

    Auth::shouldReceive('user')->andReturn(null);
    Auth::shouldReceive('id')->andReturn(null);

    config()->set('laravel-event-logs.enabled', true);
    config()->set('laravel-event-logs.exclude_routes', []);
    config()->set('laravel-event-logs.sanitize.request_headers_exclude', ['authorization', 'cookie']);
    config()->set('laravel-event-logs.sanitize.request_data_exclude', []);

    $middleware = new EventLogMiddleware;
    $response = new Response('OK', 200);

    $middleware->handle($request, function ($req) use ($response) {
        return $response;
    });

    $eventLog = EventLog::where('request_route', 'api.users.store')->first();
    expect($eventLog)->not->toBeNull();
    expect($eventLog->request_headers)->not->toHaveKey('authorization');
    expect($eventLog->request_headers)->not->toHaveKey('cookie');
    expect($eventLog->request_headers)->toHaveKey('content-type');
});

test('middleware sanitizes request data', function () {
    $route = Mockery::mock(Route::class);
    $route->shouldReceive('getName')->andReturn('api.users.store');

    $request = Request::create('https://example.com/api/users', 'POST', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'token' => 'abc123',
    ]);
    $request->setRouteResolver(function () use ($route) {
        return $route;
    });
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    Auth::shouldReceive('user')->andReturn(null);
    Auth::shouldReceive('id')->andReturn(null);

    config()->set('laravel-event-logs.enabled', true);
    config()->set('laravel-event-logs.exclude_routes', []);
    config()->set('laravel-event-logs.sanitize.request_headers_exclude', []);
    config()->set('laravel-event-logs.sanitize.request_data_exclude', ['password', 'password_confirmation', 'token']);

    $middleware = new EventLogMiddleware;
    $response = new Response('OK', 200);

    $middleware->handle($request, function ($req) use ($response) {
        return $response;
    });

    $eventLog = EventLog::where('request_route', 'api.users.store')->first();
    expect($eventLog)->not->toBeNull();
    expect($eventLog->request_data)->not->toHaveKey('password');
    expect($eventLog->request_data)->not->toHaveKey('password_confirmation');
    expect($eventLog->request_data)->not->toHaveKey('token');
    expect($eventLog->request_data)->toHaveKey('name');
    expect($eventLog->request_data)->toHaveKey('email');
});

test('middleware handles authenticated user', function () {
    $route = Mockery::mock(Route::class);
    $route->shouldReceive('getName')->andReturn('api.users.store');

    $user = Mockery::mock('App\Models\User');
    $user->shouldReceive('getKey')->andReturn(42);
    $user->id = 42;

    $request = Request::create('https://example.com/api/users', 'POST');
    $request->setRouteResolver(function () use ($route) {
        return $route;
    });
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    // Set user resolver on the request - this works for the default guard
    // But the middleware calls $request->user($guard) with specific guards
    // So we also need to mock Auth::guard() for those guards
    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    // Configure auth guards so the middleware can iterate through them
    config()->set('auth.guards', [
        'web' => ['driver' => 'session', 'provider' => 'users'],
    ]);

    // Mock Auth::guard() to return a guard that has the user for any guard
    $guard = Mockery::mock();
    $guard->shouldReceive('user')->andReturn($user);
    Auth::shouldReceive('guard')->zeroOrMoreTimes()->andReturn($guard);

    config()->set('laravel-event-logs.enabled', true);
    config()->set('laravel-event-logs.exclude_routes', []);
    config()->set('laravel-event-logs.sanitize.request_headers_exclude', []);
    config()->set('laravel-event-logs.sanitize.request_data_exclude', []);

    $middleware = new EventLogMiddleware;
    $response = new Response('OK', 200);

    $middleware->handle($request, function ($req) use ($response) {
        return $response;
    });

    $eventLog = EventLog::where('request_route', 'api.users.store')->first();
    expect($eventLog)->not->toBeNull();
    expect($eventLog->user_id)->toBe(42);
    expect($eventLog->user_type)->toContain('App_Models_User');
});

test('middleware handles exclude_routes as non-array', function () {
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
    config()->set('laravel-event-logs.exclude_routes', 'not-an-array');
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
});
