<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Admin;
use App\Traits\ActivityLogger;

class LoginController extends Controller
{
    use ActivityLogger;

    public function index()
    {
        if(Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        $email = $request->email;
        $ip = $request->ip();
        $key = 'login_attempts:' . Str::lower($email) . ':' . $ip;

        // Rate limiter: block after 5 failed attempts for 15 minutes
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = $seconds / 60;
            return response()->json([
                'status' => false,
                'message' => "Too many login attempts. Please try again in $minutes minutes."
            ], 429);
        }

        $admin = Admin::where('email', $email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {

            // Log failed attempt
            $this->logActivity([
                'module' => 'auth',
                'action' => 'login',
                'description' => "Failed login attempt for email $email",
                'meta' => [
                    'ip' => $ip,
                    'user_agent' => $request->userAgent()
                ]
            ]);

            // Increment attempts
            RateLimiter::hit($key, 900);

            return response()->json([
                'status' => false,
                'message' => 'Invalid credentials.'
            ], 401);
        }

        if(!$admin->status) {
            return response()->json([
                'status' => false,
                'message' => 'This account is not active. Please contact to your Admin.'
            ], 401);
        }

        // Successful login
        Auth::guard('admin')->login($admin, true);
        $request->session()->regenerate();

        session([
            'session_start' => now(),
        ]);

        // Clear failed attempts
        RateLimiter::clear($key);

        // Activity log
        $this->logActivity([
            'module' => 'auth',
            'action' => 'login',
            'model' => 'Admin',
            'model_id' => $admin->id,
            'description' => 'Admin logged in successfully',
            'meta' => [
                'ip' => $ip,
                'user_agent' => $request->userAgent(),
                'login_at' => now()
            ]
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Login successful.',
            'goto' => route('admin.dashboard')
        ]);
    }

    public function logout(Request $request)
    {
        // Get current admin
        $admin = auth()->guard('admin')->user();

        if ($admin) {
            $this->logActivity([
                'module' => 'auth',
                'action' => 'logout',
                'model' => 'Admin',
                'model_id' => $admin->id,
                'description' => 'Admin logged out successfully',
                'meta' => [
                    'ip' => request()->ip(),
                    'user_agent' => $request->userAgent(),
                    'logout_at' => now()
                ]
            ]);
        }

        // Logout admin guard
        Auth::guard('admin')->logout();

        // Clear all session data and regenerate token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'status' => true,
            'goto' => route('admin.login'),
            'message' => 'Logout successful'
        ]);
    }
}
