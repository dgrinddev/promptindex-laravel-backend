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
        if (Cache::add('cleanup:test-user-prompts:lock', true, now()->addSeconds(45))) {
            $testEmail = config('test_user.email');
            $cleanupTtlMinutes = (int) config('test_user.prompt_cleanup_ttl_minutes', 30);

            if ($testEmail) {
                $testUserId = User::query()
                    ->where('email', $testEmail)
                    ->value('id');

                if ($testUserId) {
                    Prompt::query()
                        ->where('user_id', $testUserId)
                        ->where('created_at', '<', now()->subMinutes($cleanupTtlMinutes))
                        ->delete();
                }
            }
        }

        return $next($request);
    }
}