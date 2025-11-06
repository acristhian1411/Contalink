<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CspReportController extends Controller
{
    /**
     * @brief Handle incoming Content Security Policy violation reports for security monitoring
     * 
     * Processes CSP violation reports from browsers, logs them for security analysis,
     * and optionally notifies the security team in production environments.
     *
     * @param Request $request The HTTP request containing the CSP violation report
     * @return JsonResponse Returns status confirmation with 204 on success, 500 on error
     * @throws \Exception When report processing fails or logging encounters errors
     */
    public function report(Request $request): JsonResponse
    {
        try {
            $report = $request->json()->all();
            
            // Log CSP violation for security monitoring
            Log::channel('security')->warning('CSP Violation Detected', [
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'report' => $report,
                'timestamp' => now()->toISOString(),
            ]);

            // In production, you might want to send this to a security monitoring service
            if (app()->environment('production')) {
                $this->notifySecurityTeam($report, $request);
            }

            return response()->json(['status' => 'received'], 204);
        } catch (\Exception $e) {
            Log::error('Failed to process CSP report', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * @brief Send high-priority CSP violation alerts to security team for immediate attention
     * 
     * Logs critical security events when CSP violations occur in production,
     * providing detailed context for security monitoring and incident response.
     *
     * @param array $report The CSP violation report data from the browser
     * @param Request $request The original HTTP request context for additional logging
     * @return void
     */
    private function notifySecurityTeam(array $report, Request $request): void
    {
        // This could integrate with your security monitoring system
        // For now, we'll just log it with high priority
        Log::channel('security')->critical('High Priority CSP Violation', [
            'report' => $report,
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'url' => $request->url(),
        ]);
    }
}