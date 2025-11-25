<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceController extends Controller
{
    public function index(Request $request)
    {
        $tokens = $request->user()->tokens()
            ->orderBy('last_used_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($token) use ($request) {
                return [
                    'id' => $token->id,
                    'ip_address' => $token->ip_address,
                    'user_agent' => $token->user_agent,
                    'is_current_device' => $token->id === $request->user()->currentAccessToken()->id,
                    'last_used_at' => $token->last_used_at,
                    'created_at' => $token->created_at,
                ];
            });

        return response()->json($tokens);
    }

    public function destroy(Request $request, $id)
    {
        $request->user()->tokens()->where('id', $id)->delete();

        return response()->json(['message' => 'Session revoked']);
    }
}
