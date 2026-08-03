<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Support\ApiKeyGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

class ApiClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureMasterKey($request);

        return response()->json([
            'data' => ApiClient::query()
                ->withCount('devices')
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureMasterKey($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:api_clients,email'],
            'daily_message_limit' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'min_delay_seconds' => ['sometimes', 'integer', 'min:1', 'max:3600'],
        ]);
        $key = ApiKeyGenerator::generate();
        $client = ApiClient::create([
            ...$validated,
            'key_prefix' => $key['prefix'],
            'api_key_hash' => $key['hash'],
            'encrypted_api_key' => Crypt::encryptString($key['plain_text']),
        ]);

        return response()->json([
            'message' => 'API client berhasil dibuat. Simpan API key ini karena tidak dapat ditampilkan lagi.',
            'data' => $client,
            'api_key' => $key['plain_text'],
        ], 201);
    }

    public function update(Request $request, ApiClient $apiClient): JsonResponse
    {
        $this->ensureMasterKey($request);
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('api_clients')->ignore($apiClient)],
            'daily_message_limit' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'min_delay_seconds' => ['sometimes', 'integer', 'min:1', 'max:3600'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $apiClient->update($validated);

        return response()->json(['data' => $apiClient->fresh()->loadCount('devices')]);
    }

    public function regenerate(Request $request, ApiClient $apiClient): JsonResponse
    {
        $this->ensureMasterKey($request);
        $key = ApiKeyGenerator::generate();
        $apiClient->update([
            'key_prefix' => $key['prefix'],
            'api_key_hash' => $key['hash'],
            'encrypted_api_key' => Crypt::encryptString($key['plain_text']),
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'API key lama sudah tidak berlaku. Simpan key baru ini.',
            'data' => $apiClient->fresh()->loadCount('devices'),
            'api_key' => $key['plain_text'],
        ]);
    }

    private function ensureMasterKey(Request $request): void
    {
        abort_if($request->attributes->get('api_client') instanceof ApiClient, 403, 'Endpoint ini hanya untuk administrator.');
    }
}
