<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

final class AuthService
{
    /**
     * @param  array{email: string, password: string}  $credentials
     * @return array{user: User, token: string}|null
     */
    public function login(array $credentials): ?array
    {
        $user = User::query()->where('email', $credentials['email'])->first();

        // Unknown emails still perform one hash check so timing does not directly reveal
        // whether the account exists. This is a partial mitigation, not a timing guarantee.
        $passwordHash = $user?->password ?? (string) config('auth.dummy_password_hash');
        $passwordMatches = Hash::check($credentials['password'], $passwordHash);

        if (! $user || ! $passwordMatches) {
            return null;
        }

        return [
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }

    public function profile(User $user): User
    {
        return $user;
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }
}
