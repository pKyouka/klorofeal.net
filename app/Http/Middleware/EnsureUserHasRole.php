<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        // Support middleware role arguments passed as comma-separated values
        // and/or multiple middleware parameters.
        $allowedRoles = collect($roles)
            ->flatMap(fn (string $group) => explode(',', $group))
            ->map(fn (string $role): string => strtolower(trim($role)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($allowedRoles === [] || ! $user->hasAnyRole($allowedRoles)) {
            abort(403, 'You are not authorized to access this resource.');
        }

        return $next($request);
    }
}
