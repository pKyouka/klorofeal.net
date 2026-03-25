<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            $workspace = $user->workspace;

            if (! $workspace) {
                abort(403, 'This account is not assigned to any workspace.');
            }

            app()->instance('workspace', $workspace);
        }

        return $next($request);
    }
}
