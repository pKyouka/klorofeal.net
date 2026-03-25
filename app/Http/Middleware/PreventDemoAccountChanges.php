<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventDemoAccountChanges
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (config('demo.enabled') && $user && $user->isDemo()) {
            return redirect()
                ->back()
                ->with('status', 'Demo account credentials and profile cannot be changed.');
        }

        return $next($request);
    }
}
