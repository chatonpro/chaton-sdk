<?php

namespace Chaton\SDK\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Chaton\SDK\Contracts\LicenseInterface;

class EnsureSaasEnabled
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
        // First check if license is valid
        if (!$this->license->isValid()) {
            return $this->handleFeatureNotAvailable($request, 'Invalid license');
        }

        // Check if SAAS is enabled
        if (!$this->license->isSaasEnabled()) {
            return $this->handleFeatureNotAvailable(
                $request,
                'SAAS features are only available with Extended license'
            );
        }

        return $next($request);
    }

    /**
     * Handle feature not available scenario
     */
    protected function handleFeatureNotAvailable(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'error' => 'FEATURE_NOT_AVAILABLE',
                'license_type' => $this->license->getLicenseType(),
                'upgrade_required' => true,
            ], 403);
        }

        return response()->view('chaton-license::feature-locked', [
            'message' => $message,
            'license_type' => $this->license->getLicenseType(),
            'feature' => 'SAAS',
        ], 403);
    }
}
