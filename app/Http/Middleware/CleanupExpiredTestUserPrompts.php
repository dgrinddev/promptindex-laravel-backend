<?php

namespace App\Http\Middleware;

use App\Models\Prompt;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CleanupExpiredTestUserPrompts
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $testEmail = env('USER_TEST_EMAIL');

        if (!$testEmail) {
            return $next($request);
        }
    
        // If the test user is actively logged in, skip cleanup for now.
        $currentUser = $request->user();
        if ($currentUser && $currentUser->email === $testEmail) {
            return $next($request);
        }
    
        // Throttle cleanup (run at most about every 45 seconds).
        if (Cache::add('cleanup:test-user-prompts:lock', true, now()->addSeconds(45))) {
            $testUserId = User::query()
                ->where('email', $testEmail)
                ->value('id');
    
            if ($testUserId) {
                Prompt::query()
                    ->where('user_id', $testUserId)
                    ->delete();
            }
        }

        return $next($request);
    }
}