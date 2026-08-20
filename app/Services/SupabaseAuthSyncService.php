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
        Log::info('SUPABASE PASSWORD SYNC CONFIG', [
            'configured' => $this->isConfigured(),
            'has_url' => $this->baseUrl !== '',
            'has_service_key' => $this->serviceKey !== '',
        ]);

        if (! $this->isConfigured() || ! filled($user->email)) {
            Log::warning('SUPABASE PASSWORD SYNC ABORTED', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return false;
        }

        $lookup = $this->findSupabaseUserIdByEmail($user->email);
        $supabaseUserId = $lookup['id'];
        $supabaseEmail = $lookup['email'];

        Log::info('SUPABASE USER LOOKUP RESULT', [
            'laravel_user_id' => $user->id,
            'email' => $user->email,
            'supabase_user_id' => $supabaseUserId,
            'supabase_email' => $supabaseEmail,
        ]);

        if (! $supabaseUserId) {
            $syncResult = $this->syncUser($user, $password);

            Log::info('SUPABASE PASSWORD CREATE RESULT', [
                'laravel_user_id' => $user->id,
                'email' => $user->email,
                'successful' => $syncResult,
            ]);

            return $syncResult;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->serviceKey,
            'apikey' => $this->serviceKey,
            'Content-Type' => 'application/json',
        ])->put($this->baseUrl.'/auth/v1/admin/users/'.$supabaseUserId, [
            'password' => $password,
            'email_confirm' => true,
        ]);

        Log::info('SUPABASE PASSWORD PATCH RESULT', [
            'laravel_user_id' => $user->id,
            'email' => $user->email,
            'supabase_user_id' => $supabaseUserId,
            'status' => $response->status(),
            'successful' => $response->successful(),
        ]);

        if (! $response->successful()) {
            Log::warning('Supabase auth password update failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'status' => $response->status(),
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

        $lookup = $this->findSupabaseUserIdByEmail($user->email);
        $supabaseUserId = $lookup['id'];

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

    protected function findSupabaseUserIdByEmail(string $email): array
    {
        $targetEmail = strtolower(trim($email));
        $page = 1;
        $perPage = 50;

        while (true) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->serviceKey,
                'apikey' => $this->serviceKey,
            ])->get($this->baseUrl.'/auth/v1/admin/users', [
                'page' => $page,
                'per_page' => $perPage,
            ]);

            if (! $response->successful()) {
                Log::warning('Supabase admin list-users request failed', [
                    'page' => $page,
                    'status' => $response->status(),
                ]);

                return ['id' => null, 'email' => null];
            }

            $users = $response->json('users') ?? [];

            if (! is_array($users) || $users === []) {
                return ['id' => null, 'email' => null];
            }

            foreach ($users as $supabaseUser) {
                $supabaseEmail = strtolower(trim((string) ($supabaseUser['email'] ?? '')));

                if ($supabaseEmail === $targetEmail) {
                    return [
                        'id' => (string) ($supabaseUser['id'] ?? ''),
                        'email' => (string) ($supabaseUser['email'] ?? ''),
                    ];
                }
            }

            if (count($users) < $perPage) {
                break;
            }

            $page++;
        }

        Log::warning('Supabase findByEmail: no exact match found', [
            'target_email' => $targetEmail,
            'pages_searched' => $page,
        ]);

        return ['id' => null, 'email' => null];
    }
}
