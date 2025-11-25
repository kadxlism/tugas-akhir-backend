<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserProfileController extends Controller
{
    /**
     * Update user's name
     */
    public function updateName(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $user->name = $request->name;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Nama berhasil diperbarui',
            'user' => $user
        ]);
    }

    /**
     * Update user's email (requires password verification)
     */
    public function updateEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email,' . $request->user()->id,
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password yang Anda masukkan salah'
            ], 401);
        }

        $user->email = $request->email;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Email berhasil diperbarui',
            'user' => $user
        ]);
    }

    /**
     * Update user's password (requires old password verification)
     */
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama yang Anda masukkan salah'
            ], 401);
        }

        // Check if new password is same as old password
        if (Hash::check($request->new_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password baru tidak boleh sama dengan password lama'
            ], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui'
        ]);
    }
}
