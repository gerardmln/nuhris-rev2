<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SupabaseAuthSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SupabaseAuthSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sync_creates_supabase_auth_account(): void
    {
        Http::fake([
            '*/auth/v1/admin/users*' => Http::response(['id' => 'supabase-user-123'], 200),
        ]);

        $user = User::factory()->create([
            'email' => 'employee@example.com',
            'user_type' => User::TYPE_EMPLOYEE,
        ]);

        $service = app(SupabaseAuthSyncService::class);
        $result = $service->syncUser($user, 'Temp-1234');

        $this->assertTrue($result);
        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), '/auth/v1/admin/users')
                && $request->method() === 'POST'
                && $request->data()['email'] === 'employee@example.com'
                && $request->data()['password'] === 'Temp-1234';
        });
    }
}
