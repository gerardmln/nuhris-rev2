<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SupabaseAuthSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Synchronize password to Supabase Auth
        $syncSuccess = app(SupabaseAuthSyncService::class)->updateUserPassword($request->user(), $validated['password']);
        
        if (!$syncSuccess) {
            throw new RuntimeException('Failed to synchronize password with authentication service. Please try again.');
        }

        return back()->with('status', 'password-updated');
    }
}
