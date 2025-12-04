# Production Hardening Checklist

## Critical Security Settings

### 1. Environment Configuration
```bash
# .env - Production Settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://baraka.sanaa.co

# Session Security
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict

# Database Security
DB_CONNECTION=mysql
DB_STRICT=true
```

### 2. Disable Debug Mode
```bash
# In .env
APP_DEBUG=false

# Verify with:
php artisan config:cache
php artisan route:cache
```

### 3. HTTPS Enforcement
Add to `app/Http/Middleware/TrustProxies.php` or nginx config:
```nginx
server {
    listen 80;
    return 301 https://$host$request_uri;
}
```

### 4. Security Headers (nginx)
```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

### 5. Rate Limiting
Already configured via `throttle` middleware. Verify in `app/Http/Kernel.php`.

### 6. MFA Enforcement
Enable in admin dashboard: Admin > Security > MFA Settings
- Require MFA for admin roles
- Optional for branch users

### 7. Password Policies
Configured in `security_settings` table:
- Minimum 12 characters
- Require special characters
- Password expiry: 90 days

### 8. API Security
- API keys with scoped permissions
- Rate limiting per key
- Request logging enabled

### 9. Database Encryption
- Sensitive fields encrypted at rest
- Use encrypted backups

### 10. Monitoring
- Enable error reporting (Sentry/Bugsnag)
- Set up health checks
- Configure alerting for:
  - Failed login attempts > 10/min
  - 5xx errors > 1%
  - API latency > 500ms

## Deployment Commands
```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

# Set proper permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Penetration Testing Checklist
1. SQL Injection - Parameterized queries (Laravel Eloquent)
2. XSS - Blade escaping, CSP headers
3. CSRF - Token validation on all forms
4. Authentication - MFA, lockout, session management
5. Authorization - RBAC with Spatie permissions
6. File Upload - Validation, virus scanning
7. API Security - Rate limiting, auth, input validation
