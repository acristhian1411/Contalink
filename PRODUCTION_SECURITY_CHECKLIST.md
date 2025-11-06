# Production Security Checklist

This document provides a comprehensive checklist for deploying the Contalink application securely in production.

## Environment Configuration

### Required Environment Variables

```bash
# Application Security
APP_ENV=production
APP_DEBUG=false
APP_KEY=<generate-with-php-artisan-key:generate>

# Session Security
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict
SESSION_LIFETIME=60

# Database Security
DB_SSLMODE=require  # For PostgreSQL/MySQL with SSL

# Sanctum Configuration
SANCTUM_EXPIRATION=60
SANCTUM_TOKEN_PREFIX=contalink_
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com

# CORS Configuration
CORS_ALLOWED_ORIGINS=https://yourdomain.com,https://www.yourdomain.com

# Security Features
FORCE_HTTPS=true
HSTS_ENABLED=true
SECURITY_HEADERS_ENABLED=true
CSP_ENABLED=true
CSP_REPORTING_ENABLED=true

# Logging Configuration
LOG_SECURITY_LEVEL=warning
LOG_SECURITY_DAYS=90
LOG_AUDIT_LEVEL=info
LOG_AUDIT_DAYS=365
LOG_AUTH_LEVEL=info
LOG_AUTH_DAYS=30

# Rate Limiting
THROTTLE_API_REQUESTS=60
THROTTLE_AUTH_REQUESTS=5
```

## Pre-Deployment Security Validation

Run the security validation command before deploying:

```bash
php artisan security:validate-production
```

This command will check:
- ✅ Debug mode is disabled
- ✅ Environment is set to production
- ✅ Session security is properly configured
- ✅ Database SSL is enabled (if applicable)
- ✅ Security headers are enabled
- ✅ CORS is properly restricted
- ✅ Security logging is configured
- ✅ Sanctum token expiration is reasonable

## Security Features Implemented

### 1. Security Headers
- **X-Frame-Options**: Prevents clickjacking attacks
- **X-Content-Type-Options**: Prevents MIME type sniffing
- **X-XSS-Protection**: Enables browser XSS filtering
- **Referrer-Policy**: Controls referrer information
- **Strict-Transport-Security**: Forces HTTPS (production only)

### 2. Content Security Policy (CSP)
- Prevents XSS attacks by controlling resource loading
- Different policies for development and production
- CSP violation reporting in production
- Report endpoint: `/csp-report`

### 3. Authentication & Authorization
- Laravel Sanctum SPA authentication
- Rate limiting on authentication endpoints (5 requests/minute)
- Token expiration (60 minutes default)
- Permission-based access control

### 4. API Security
- All API routes require authentication (`auth:sanctum`)
- Rate limiting (60 requests/minute default)
- CSRF protection for SPA requests
- Mass assignment protection

### 5. Session Security
- Session encryption enabled
- Secure cookies (HTTPS only)
- HTTP-only cookies
- SameSite protection (strict)

### 6. Input Validation & Sanitization
- Comprehensive Form Request classes
- XSS protection on all inputs
- File upload validation
- Business rule validation

### 7. Security Logging
- Dedicated security log channel
- Authentication event logging
- CSP violation logging
- Audit trail logging

## Deployment Steps

### 1. Server Configuration
- Ensure HTTPS is properly configured
- Set up SSL certificates
- Configure web server security headers (as backup)
- Enable fail2ban or similar intrusion prevention

### 2. Database Security
- Enable SSL connections
- Use strong database passwords
- Restrict database access to application servers only
- Regular security updates

### 3. Application Deployment
```bash
# 1. Deploy code
git clone <repository>
cd <application-directory>

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Set up environment
cp .env.example .env
# Edit .env with production values

# 4. Generate application key
php artisan key:generate

# 5. Run migrations
php artisan migrate --force

# 6. Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Validate security
php artisan security:validate-production

# 8. Set proper permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 755 storage bootstrap/cache
```

### 4. Post-Deployment Verification
- [ ] Verify HTTPS redirection works
- [ ] Test authentication flows
- [ ] Verify CSP headers are present
- [ ] Check security logs are being written
- [ ] Test rate limiting functionality
- [ ] Verify API authentication requirements

## Monitoring & Maintenance

### Security Monitoring
- Monitor security logs for suspicious activity
- Set up alerts for CSP violations
- Monitor authentication failure rates
- Regular security audits

### Log Rotation
Security logs are configured with retention periods:
- Security logs: 90 days
- Audit logs: 365 days
- Authentication logs: 30 days

### Regular Tasks
- [ ] Update dependencies monthly
- [ ] Review security logs weekly
- [ ] Rotate application keys annually
- [ ] Update SSL certificates before expiration
- [ ] Review and update CSP policies as needed

## Incident Response

### Security Incident Checklist
1. **Immediate Response**
   - Identify and contain the threat
   - Preserve evidence (logs, system state)
   - Notify stakeholders

2. **Investigation**
   - Review security logs
   - Check for data breaches
   - Identify attack vectors

3. **Recovery**
   - Patch vulnerabilities
   - Update security configurations
   - Monitor for continued threats

4. **Post-Incident**
   - Document lessons learned
   - Update security procedures
   - Improve monitoring

## Security Contacts

- **Security Team**: security@yourcompany.com
- **Emergency Contact**: +1-XXX-XXX-XXXX
- **Incident Reporting**: incidents@yourcompany.com

## Additional Resources

- [Laravel Security Documentation](https://laravel.com/docs/security)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [CSP Reference](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP)
- [Sanctum Documentation](https://laravel.com/docs/sanctum)