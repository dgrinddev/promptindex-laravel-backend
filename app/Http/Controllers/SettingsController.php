<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProfileEditResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    /**
     * Get the specified resource with specific fields.
     */
    public function editProfile(Request $request)
    {
        return new ProfileEditResource($request->user());
    }

    public function deleteAccount(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string', 'current_password:web'],
        ]);

        $user = $request->user();
        
        Auth::guard('web')->logout();
        
        $user->delete();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response(null, 204);
    }
}