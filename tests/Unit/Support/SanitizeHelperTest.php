<?php

use CodebarAg\LaravelEventLogs\Support\SanitizeHelper;

test('removeKeys removes specified keys from array', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'secret123',
        'age' => 30,
        'token' => 'abc123',
    ];

    $keysToRemove = ['password', 'token'];

    $result = SanitizeHelper::removeKeys($data, $keysToRemove);

    expect($result)
        ->toHaveKey('name', 'John Doe')
        ->toHaveKey('email', 'john@example.com')
        ->toHaveKey('age', 30)
        ->not->toHaveKey('password')
        ->not->toHaveKey('token');
});

test('removeKeys handles empty array', function () {
    $data = [];
    $keysToRemove = ['password', 'token'];

    $result = SanitizeHelper::removeKeys($data, $keysToRemove);

    expect($result)->toBe([]);
});

test('removeKeys handles empty keys array', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ];

    $keysToRemove = [];

    $result = SanitizeHelper::removeKeys($data, $keysToRemove);

    expect($result)->toBe($data);
});

test('removeKeys handles nested arrays', function () {
    $data = [
        'user' => [
            'name' => 'John Doe',
            'password' => 'secret123',
            'profile' => [
                'age' => 30,
                'token' => 'abc123',
            ],
        ],
        'settings' => [
            'theme' => 'dark',
            'api_key' => 'xyz789',
        ],
    ];

    $keysToRemove = ['password', 'token', 'api_key'];

    $result = SanitizeHelper::removeKeys($data, $keysToRemove);

    expect($result)
        ->toHaveKey('user', [
            'name' => 'John Doe',
            'password' => 'secret123',
            'profile' => [
                'age' => 30,
                'token' => 'abc123',
            ],
        ])
        ->toHaveKey('settings', [
            'theme' => 'dark',
            'api_key' => 'xyz789',
        ])
        ->not->toHaveKey('password')
        ->not->toHaveKey('token')
        ->not->toHaveKey('api_key');
});

test('removeKeys handles non-existent keys', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ];

    $keysToRemove = ['password', 'token', 'nonexistent'];

    $result = SanitizeHelper::removeKeys($data, $keysToRemove);

    expect($result)
        ->toHaveKey('name', 'John Doe')
        ->toHaveKey('email', 'john@example.com');
});

test('removeKeys preserves original array', function () {
    $data = [
        'name' => 'John Doe',
        'password' => 'secret123',
    ];

    $keysToRemove = ['password'];

    $result = SanitizeHelper::removeKeys($data, $keysToRemove);

    expect($data)->toHaveKey('password', 'secret123');
    expect($result)->not->toHaveKey('password');
});

test('removeKeys handles mixed data types', function () {
    $data = [
        'string' => 'value',
        'integer' => 42,
        'boolean' => true,
        'null' => null,
        'array' => [1, 2, 3],
        'object' => (object) ['key' => 'value'],
    ];

    $keysToRemove = ['boolean', 'null'];

    $result = SanitizeHelper::removeKeys($data, $keysToRemove);

    expect($result)
        ->toHaveKey('string', 'value')
        ->toHaveKey('integer', 42)
        ->toHaveKey('array', [1, 2, 3])
        ->toHaveKey('object', (object) ['key' => 'value'])
        ->not->toHaveKey('boolean')
        ->not->toHaveKey('null');
});
