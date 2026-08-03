<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        return view('dashboard', $this->viewData($request));
    }

    public function docs(Request $request): View
    {
        return view('docs', $this->viewData($request));
    }

    public function pair(Request $request, Device $device): View
    {
        $user = $this->user($request);
        if ($user && $device->api_client_id !== $user->api_client_id) {
            abort(404);
        }

        return view('pair', [...$this->viewData($request), 'device' => $device]);
    }

    private function viewData(Request $request): array
    {
        $isAdmin = $request->session()->get('wa_admin_authenticated', false);
        $user = $this->user($request);
        $apiKey = $isAdmin
            ? (string) config('wa-gateway.api_key')
            : Crypt::decryptString((string) $user?->apiClient?->encrypted_api_key);

        return compact('apiKey', 'isAdmin', 'user');
    }

    private function user(Request $request): ?User
    {
        $userId = $request->session()->get('wa_user_id');

        return $userId ? User::with('apiClient')->find($userId) : null;
    }
}
