<?php

namespace Chaton\SDK\Middleware;

use Chaton\SDK\Contracts\LicenseInterface;
use Chaton\SDK\Exceptions\LicenseException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLicenseValid
{
    protected LicenseInterface $license;

    public function __construct(LicenseInterface $license)
    {
        $this->license = $license;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (! $this->license->isValid()) {
                return $this->handleInvalidLicense($request);
            }

            return $next($request);

        } catch (LicenseException $e) {
            return $this->handleInvalidLicense($request, $e->getMessage());
        }
    }

    /**
     * Handle invalid license scenario
     */
    protected function handleInvalidLicense(Request $request, ?string $message = null): Response
    {
        $strictMode = config('chaton-license.strict_mode', true);

        if ($strictMode) {
            // In strict mode, completely block access
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message ?? 'Invalid or expired license. Please activate your license.',
                    'error' => 'LICENSE_INVALID',
                ], 403);
            }

            return response()->view('chaton-license::invalid', [
                'message' => $message ?? 'Invalid or expired license.',
            ], 403);
        }

        // In non-strict mode, show warning but allow access
        session()->flash('license_warning', $message ?? 'Your license needs attention.');

        return $next($request);
    }
}
