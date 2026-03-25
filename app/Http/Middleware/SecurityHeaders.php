<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (config('security.headers', []) as $name => $value) {
            if ($value !== null && $value !== '') {
                $response->headers->set($name, $value);
            }
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $this->allowViteHotReloadInLocal($response);

        return $response;
    }

    private function allowViteHotReloadInLocal(Response $response): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $hotFile = public_path('hot');
        if (! is_file($hotFile)) {
            return;
        }

        $hotUrl = trim((string) file_get_contents($hotFile));
        if ($hotUrl === '') {
            return;
        }

        $parts = parse_url($hotUrl);
        $host = $parts['host'] ?? null;
        if (! is_string($host) || $host === '') {
            return;
        }

        $scheme = (string) ($parts['scheme'] ?? 'http');
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $origin = $scheme . '://' . $host . $port;
        $wsOrigin = ($scheme === 'https' ? 'wss' : 'ws') . '://' . $host . $port;

        $policy = (string) $response->headers->get('Content-Security-Policy', '');
        if ($policy === '') {
            return;
        }

        $policy = $this->mergeCspSources($policy, 'script-src', [$origin]);
        $policy = $this->mergeCspSources($policy, 'style-src', [$origin]);
        // Keep self allowed for in-app AJAX calls (e.g. POS product search) while enabling Vite HMR.
        $policy = $this->mergeCspSources($policy, 'connect-src', ["'self'", $origin, $wsOrigin]);

        $response->headers->set('Content-Security-Policy', $policy);
    }

    /**
     * Append CSP sources to an existing directive, or create the directive when missing.
     */
    private function mergeCspSources(string $policy, string $directive, array $sources): string
    {
        $sources = array_values(array_filter(array_unique($sources), static fn ($value) => is_string($value) && $value !== ''));
        if ($sources === []) {
            return $policy;
        }

        $parts = array_values(array_filter(array_map('trim', explode(';', $policy)), static fn ($value) => $value !== ''));
        $matched = false;

        foreach ($parts as &$part) {
            if (! str_starts_with($part, $directive . ' ') && $part !== $directive) {
                continue;
            }

            $tokens = preg_split('/\s+/', trim($part)) ?: [];
            $existing = array_slice($tokens, 1);

            foreach ($sources as $source) {
                if (! in_array($source, $existing, true)) {
                    $existing[] = $source;
                }
            }

            $part = $directive . ' ' . implode(' ', $existing);
            $matched = true;
            break;
        }
        unset($part);

        if (! $matched) {
            $parts[] = $directive . ' ' . implode(' ', $sources);
        }

        return implode('; ', $parts) . ';';
    }
}
