<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class ValidateProductionSecurity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:validate-production {--fix : Attempt to fix common issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate production security configuration';

    /**
     * Security checks to perform
     *
     * @var array
     */
    protected $checks = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔒 Validating Production Security Configuration...');
        $this->newLine();

        $this->performSecurityChecks();
        
        $passed = collect($this->checks)->where('status', 'pass')->count();
        $failed = collect($this->checks)->where('status', 'fail')->count();
        $warnings = collect($this->checks)->where('status', 'warning')->count();

        $this->newLine();
        $this->info("Security Validation Complete:");
        $this->info("✅ Passed: {$passed}");
        
        if ($warnings > 0) {
            $this->warn("⚠️  Warnings: {$warnings}");
        }
        
        if ($failed > 0) {
            $this->error("❌ Failed: {$failed}");
            return Command::FAILURE;
        }

        $this->info('🎉 All security checks passed!');
        return Command::SUCCESS;
    }

    /**
     * Perform all security checks
     */
    protected function performSecurityChecks(): void
    {
        $this->checkAppDebug();
        $this->checkAppEnvironment();
        $this->checkSessionSecurity();
        $this->checkDatabaseSecurity();
        $this->checkSecurityHeaders();
        $this->checkCorsConfiguration();
        $this->checkLoggingConfiguration();
        $this->checkSanctumConfiguration();

        $this->displayResults();
    }

    /**
     * Check if APP_DEBUG is disabled in production
     */
    protected function checkAppDebug(): void
    {
        $debug = config('app.debug');
        $env = config('app.env');

        if ($env === 'production' && $debug) {
            $this->addCheck('APP_DEBUG', 'fail', 'Debug mode is enabled in production', 'Set APP_DEBUG=false');
        } else {
            $this->addCheck('APP_DEBUG', 'pass', 'Debug mode properly configured');
        }
    }

    /**
     * Check application environment
     */
    protected function checkAppEnvironment(): void
    {
        $env = config('app.env');
        
        if (in_array($env, ['local', 'development', 'testing'])) {
            $this->addCheck('APP_ENV', 'warning', "Environment is set to '{$env}'", 'Set APP_ENV=production for production');
        } else {
            $this->addCheck('APP_ENV', 'pass', "Environment properly set to '{$env}'");
        }
    }

    /**
     * Check session security configuration
     */
    protected function checkSessionSecurity(): void
    {
        $encrypt = config('session.encrypt');
        $secure = config('session.secure_cookie');
        $httpOnly = config('session.http_only');
        $sameSite = config('session.same_site');

        if (!$encrypt) {
            $this->addCheck('SESSION_ENCRYPT', 'fail', 'Session encryption is disabled', 'Set SESSION_ENCRYPT=true');
        } else {
            $this->addCheck('SESSION_ENCRYPT', 'pass', 'Session encryption enabled');
        }

        if (!$secure && config('app.env') === 'production') {
            $this->addCheck('SESSION_SECURE', 'fail', 'Secure cookies disabled in production', 'Set SESSION_SECURE_COOKIE=true');
        } else {
            $this->addCheck('SESSION_SECURE', 'pass', 'Secure cookies properly configured');
        }

        if (!$httpOnly) {
            $this->addCheck('SESSION_HTTP_ONLY', 'fail', 'HTTP-only cookies disabled', 'Set SESSION_HTTP_ONLY=true');
        } else {
            $this->addCheck('SESSION_HTTP_ONLY', 'pass', 'HTTP-only cookies enabled');
        }

        if ($sameSite !== 'strict' && $sameSite !== 'lax') {
            $this->addCheck('SESSION_SAME_SITE', 'warning', "SameSite policy is '{$sameSite}'", 'Consider setting SESSION_SAME_SITE=strict');
        } else {
            $this->addCheck('SESSION_SAME_SITE', 'pass', "SameSite policy set to '{$sameSite}'");
        }
    }

    /**
     * Check database security
     */
    protected function checkDatabaseSecurity(): void
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (config('app.env') === 'production') {
            if (!isset($config['sslmode']) || $config['sslmode'] !== 'require') {
                $this->addCheck('DB_SSL', 'warning', 'Database SSL not enforced in production', 'Set DB_SSLMODE=require');
            } else {
                $this->addCheck('DB_SSL', 'pass', 'Database SSL properly configured');
            }
        } else {
            $this->addCheck('DB_SSL', 'pass', 'Database SSL check skipped (not production)');
        }
    }

    /**
     * Check security headers configuration
     */
    protected function checkSecurityHeaders(): void
    {
        $enabled = config('security.headers.enabled', true);
        
        if (!$enabled) {
            $this->addCheck('SECURITY_HEADERS', 'fail', 'Security headers are disabled', 'Set SECURITY_HEADERS_ENABLED=true');
        } else {
            $this->addCheck('SECURITY_HEADERS', 'pass', 'Security headers enabled');
        }

        $cspEnabled = config('security.csp.enabled', true);
        if (!$cspEnabled) {
            $this->addCheck('CSP', 'warning', 'Content Security Policy is disabled', 'Set CSP_ENABLED=true');
        } else {
            $this->addCheck('CSP', 'pass', 'Content Security Policy enabled');
        }
    }

    /**
     * Check CORS configuration
     */
    protected function checkCorsConfiguration(): void
    {
        $allowedOrigins = config('cors.allowed_origins');
        
        if (in_array('*', $allowedOrigins)) {
            $this->addCheck('CORS', 'fail', 'CORS allows all origins', 'Restrict CORS_ALLOWED_ORIGINS to specific domains');
        } else {
            $this->addCheck('CORS', 'pass', 'CORS properly restricted');
        }
    }

    /**
     * Check logging configuration
     */
    protected function checkLoggingConfiguration(): void
    {
        $channels = config('logging.channels');
        
        if (!isset($channels['security'])) {
            $this->addCheck('SECURITY_LOGGING', 'warning', 'Security logging channel not configured', 'Configure security logging channel');
        } else {
            $this->addCheck('SECURITY_LOGGING', 'pass', 'Security logging configured');
        }
    }

    /**
     * Check Sanctum configuration
     */
    protected function checkSanctumConfiguration(): void
    {
        $expiration = config('sanctum.expiration');
        
        if (!$expiration || $expiration > 1440) { // More than 24 hours
            $this->addCheck('SANCTUM_EXPIRATION', 'warning', 'Sanctum token expiration is too long or not set', 'Set SANCTUM_EXPIRATION to reasonable value (e.g., 60 minutes)');
        } else {
            $this->addCheck('SANCTUM_EXPIRATION', 'pass', "Sanctum tokens expire after {$expiration} minutes");
        }
    }

    /**
     * Add a security check result
     */
    protected function addCheck(string $name, string $status, string $message, string $recommendation = null): void
    {
        $this->checks[] = [
            'name' => $name,
            'status' => $status,
            'message' => $message,
            'recommendation' => $recommendation,
        ];
    }

    /**
     * Display check results
     */
    protected function displayResults(): void
    {
        foreach ($this->checks as $check) {
            $icon = match($check['status']) {
                'pass' => '✅',
                'fail' => '❌',
                'warning' => '⚠️',
                default => '❓'
            };

            $this->line("{$icon} {$check['name']}: {$check['message']}");
            
            if ($check['recommendation'] && $check['status'] !== 'pass') {
                $this->line("   💡 {$check['recommendation']}");
            }
        }
    }
}