<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseAuthSyncService
{
    protected string $baseUrl;

    protected string $serviceKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) env('SUPABASE_URL'), '/');
        $this->serviceKey = (string) env('SUPABASE_SERVICE_ROLE_KEY');
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->serviceKey !== '';
    }

    public function syncUser(User $user, ?string $password = null): bool
    {
        if (! $this->isConfigured() || ! filled($user->email)) {
            return false;
        }

        $payload = [
            'email' => $user->email,
            'email_confirm' => true,
            'user_metadata' => [
                'laravel_user_id' => $user->id,
                'user_type' => (int) ($user->user_type ?? 0),
            ],
        ];

        if (filled($password)) {
            $payload['password'] = $password;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->serviceKey,
            'apikey' => $this->serviceKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl.'/auth/v1/admin/users', $payload);

        if (! $response->successful()) {
            Log::warning('Supabase auth sync failed for user creation', [
                'user_id' => $user->id,
                'email' => $user->email,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    public function updateUserPassword(User $user, string $password): bool
    {
        if (! $this->isConfigured() || ! filled($user->email)) {
            return false;
        }

        $supabaseUserId = $this->findSupabaseUserIdByEmail($user->email);

        if (! $supabaseUserId) {
            return $this->syncUser($user, $password);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->serviceKey,
            'apikey' => $this->serviceKey,
            'Content-Type' => 'application/json',
        ])->patch($this->baseUrl.'/auth/v1/admin/users/'.$supabaseUserId, [
            'password' => $password,
            'email_confirm' => true,
        ]);

        if (! $response->successful()) {
            Log::warning('Supabase auth password update failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    public function deleteUser(User $user): bool
    {
        if (! $this->isConfigured() || ! filled($user->email)) {
            return false;
        }

        $supabaseUserId = $this->findSupabaseUserIdByEmail($user->email);

        if (! $supabaseUserId) {
            return true;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->serviceKey,
            'apikey' => $this->serviceKey,
        ])->delete($this->baseUrl.'/auth/v1/admin/users/'.$supabaseUserId);

        if (! $response->successful()) {
            Log::warning('Supabase auth user deletion failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    protected function findSupabaseUserIdByEmail(string $email): ?string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->serviceKey,
            'apikey' => $this->serviceKey,
        ])->get($this->baseUrl.'/auth/v1/admin/users', [
            'email' => $email,
            'page' => 1,
            'per_page' => 1,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $users = $response->json('users') ?? [];

        if (! is_array($users) || $users === []) {
            return null;
        }

        return (string) ($users[0]['id'] ?? '');
    }
}
