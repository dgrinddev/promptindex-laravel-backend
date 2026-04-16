<?php

namespace App\Http\Middleware;

use App\Models\Prompt;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
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
        $testEmail = config('test_user.email');
        $cleanupTtlMinutes = (int) config('test_user.prompt_cleanup_ttl_minutes', 30);

        if ($testEmail) {
            $testUserId = User::query()
                ->where('email', $testEmail)
                ->value('id');

            if ($testUserId) {
                // each(...->delete()) per model, not ->delete() on the query: bulk delete
                // does not fire PromptObserver / ImageObserver, so image files would remain
                // on disk. Eloquent delete runs the same cleanup as normal prompt removal.
                Prompt::query()
                    ->where('user_id', $testUserId)
                    ->where('created_at', '<', now()->subMinutes($cleanupTtlMinutes))
                    ->each(fn(Prompt $prompt) => $prompt->delete());
            }
        }

        return $next($request);
    }
}
