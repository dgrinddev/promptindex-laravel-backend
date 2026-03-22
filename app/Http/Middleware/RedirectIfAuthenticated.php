<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated as BaseRedirectIfAuthenticated;

class RedirectIfAuthenticated extends BaseRedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // If the request expects a JSON response, return a 409 Conflict with a message
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Already authenticated.',
                    ], 409);
                }

                return redirect($this->redirectTo($request));
            }
        }

        return $next($request);
    }
}