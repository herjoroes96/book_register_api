<?php

use App\Models\User;

$dummyPasswordHash = (string) (env('AUTH_DUMMY_PASSWORD_HASH') ?: '$2y$12$yu1g0qE1mw13TROC1mvfzuKbUmituQCz5JxFiLmFZrxSAzRRdBUdW');
$dummyHashInfo = password_get_info($dummyPasswordHash);
$dummyHashOptions = $dummyHashInfo['options'] ?? [];
$hashDriver = (string) env('HASH_DRIVER', 'bcrypt');

$dummyHashIsValid = match ($hashDriver) {
    'bcrypt' => $dummyHashInfo['algoName'] === 'bcrypt'
        && (int) ($dummyHashOptions['cost'] ?? 0) === (int) env('BCRYPT_ROUNDS', 12),
    'argon' => $dummyHashInfo['algoName'] === 'argon2i'
        && (int) ($dummyHashOptions['memory_cost'] ?? 0) === (int) env('ARGON_MEMORY', 65536)
        && (int) ($dummyHashOptions['time_cost'] ?? 0) === (int) env('ARGON_TIME', 4)
        && (int) ($dummyHashOptions['threads'] ?? 0) === (int) env('ARGON_THREADS', 1),
    'argon2id' => $dummyHashInfo['algoName'] === 'argon2id'
        && (int) ($dummyHashOptions['memory_cost'] ?? 0) === (int) env('ARGON_MEMORY', 65536)
        && (int) ($dummyHashOptions['time_cost'] ?? 0) === (int) env('ARGON_TIME', 4)
        && (int) ($dummyHashOptions['threads'] ?? 0) === (int) env('ARGON_THREADS', 1),
    default => false,
};

if (! $dummyHashIsValid) {
    throw new RuntimeException('AUTH_DUMMY_PASSWORD_HASH must match the configured hasher and cost.');
}

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication "guard" and password
    | reset "broker" for your application. You may change these values
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | which utilizes session storage plus the Eloquent user provider.
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | If you have multiple user tables or models you may configure multiple
    | providers to represent the model / table. These providers may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | These configuration options specify the behavior of Laravel's password
    | reset functionality, including the table utilized for token storage
    | and the user provider that is invoked to actually retrieve users.
    |
    | The expiry time is the number of minutes that each reset token will be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    | The throttle setting is the number of seconds a user must wait before
    | generating more password reset tokens. This prevents the user from
    | quickly generating a very large amount of password reset tokens.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the number of seconds before a password confirmation
    | window expires and users are asked to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

    /*
    |--------------------------------------------------------------------------
    | Dummy Password Hash
    |--------------------------------------------------------------------------
    |
    | Unknown emails still perform one password hash check so login timing
    | does not reveal whether the account exists. This value is not a secret.
    | It is validated against the configured hasher and options (bcrypt 12
    | by default). Override AUTH_DUMMY_PASSWORD_HASH when changing either.
    |
    */

    'dummy_password_hash' => $dummyPasswordHash,

];
