<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Lightweight wrapper around Supabase Storage REST API.
 * Uses SUPABASE_URL + SUPABASE_SERVICE_ROLE_KEY from .env.
 *
 * Requires a Supabase storage bucket to exist (default: "credentials").
 */
class SupabaseStorageService
{
    protected string $baseUrl;
    protected string $serviceKey;
    protected string $bucket;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) env('SUPABASE_URL'), '/');
        $this->serviceKey = (string) env('SUPABASE_SERVICE_ROLE_KEY');
        $this->bucket = (string) env('SUPABASE_STORAGE_BUCKET', 'credentials');

        if ($this->baseUrl === '' || $this->serviceKey === '') {
            throw new RuntimeException('Supabase storage is not configured. Set SUPABASE_URL and SUPABASE_SERVICE_ROLE_KEY in .env.');
        }
    }

    /**
     * Upload an UploadedFile to Supabase storage and return the storage path.
     */
    public function uploadFile(UploadedFile $file, string $directory = ''): string
    {
        $directory = trim($directory, '/');
        $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $file->getClientOriginalExtension();
        $path = $directory === '' ? $filename : "{$directory}/{$filename}";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->serviceKey,
            'apikey' => $this->serviceKey,
            'Content-Type' => $file->getMimeType() ?: 'application/octet-stream',
            'x-upsert' => 'true',
        ])
            ->withBody(file_get_contents($file->getRealPath()), $file->getMimeType() ?: 'application/octet-stream')
            ->post("{$this->baseUrl}/storage/v1/object/{$this->bucket}/{$path}");

        if (! $response->successful()) {
            throw new RuntimeException('Failed to upload file to Supabase storage: ' . $response->body());
        }

        return $path;
    }

    /**
     * Create a temporary signed URL for viewing/downloading a file.
     */
    public function createSignedUrl(string $path, int $expiresIn = 3600): ?string
    {
        $path = ltrim($path, '/');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->serviceKey,
            'apikey' => $this->serviceKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/storage/v1/object/sign/{$this->bucket}/{$path}", [
            'expiresIn' => $expiresIn,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $signedPath = $response->json('signedURL') ?? $response->json('signedUrl');
        if (! $signedPath) {
            return null;
        }

        // Supabase returns a relative path; prepend the storage host.
        return $this->baseUrl . '/storage/v1' . $signedPath;
    }

    /**
     * Delete a file from storage.
     */
    public function delete(string $path): bool
    {
        $path = ltrim($path, '/');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->serviceKey,
            'apikey' => $this->serviceKey,
        ])->delete("{$this->baseUrl}/storage/v1/object/{$this->bucket}/{$path}");

        return $response->successful();
    }

    public function bucket(): string
    {
        return $this->bucket;
    }
}
