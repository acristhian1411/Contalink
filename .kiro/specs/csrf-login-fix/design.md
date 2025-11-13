# Design Document: CSRF Login Fix

## Overview

This design addresses the HTTP 419 CSRF token validation error occurring during user authentication. The root cause is the Login Component using raw `fetch()` API instead of Inertia.js form helpers, combined with potentially misconfigured session cookie settings for the development environment.

The solution involves:
1. Refactoring the Login Component to use Inertia.js form helpers
2. Adjusting session cookie configuration for development environments
3. Ensuring the AuthController returns appropriate Inertia responses

## Architecture

### Current Flow (Problematic)
```
User submits form → Svelte fetch() → POST /login → ValidateCsrfToken middleware → 419 Error
```

**Issues:**
- `fetch()` doesn't automatically handle session cookies properly
- Session cookie might not be sent due to `SESSION_SECURE_COOKIE=true` in local development
- Response is JSON instead of Inertia response

### Proposed Flow (Fixed)
```
User submits form → Axios POST → /login → ValidateCsrfToken middleware → AuthController → Inertia redirect
```

**Benefits:**
- Axios automatically includes CSRF token from meta tag
- Session cookies are properly maintained with credentials
- Simpler implementation than raw fetch()
- Better error handling

## Components and Interfaces

### 1. Login Component (`resources/js/Pages/Login/index.svelte`)

**Current Implementation Issues:**
- Uses `fetch()` API directly
- Manual CSRF token extraction
- Doesn't send credentials properly
- Poor error handling

**Proposed Changes:**
- Use axios for HTTP requests
- Configure axios to automatically include CSRF token
- Set `withCredentials: true` for cookie handling
- Proper error handling and user feedback
- Use Inertia.visit() for successful redirects

**Interface:**
```javascript
import { Inertia } from '@inertiajs/inertia';

// Note: axios is already configured globally in bootstrap.js with:
// - CSRF token from meta tag
// - withCredentials: true
// - X-Requested-With header

// Submit handler
const login = async () => {
  try {
    const response = await axios.post('/login', {
      email,
      password,
      remember
    });
    
    if (response.data.success) {
      Inertia.visit('/');
    }
  } catch (error) {
    // Handle validation errors
    if (error.response?.status === 422) {
      // Show validation errors from response.data.errors
    } else if (error.response?.status === 401) {
      // Show authentication error
    } else {
      // Show generic error
    }
  }
};
```

### 2. AuthController (`app/Http/Controllers/Auth/AuthController.php`)

**Current Implementation:**
- Returns JSON responses
- Doesn't leverage Inertia for redirects

**Proposed Changes:**
- Return Inertia redirect on success
- Return proper validation errors for Inertia
- Maintain session regeneration for security

**Interface:**
```php
public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
        $request->session()->regenerate();
        return redirect()->intended('/');
    }

    throw ValidationException::withMessages([
        'email' => __('auth.failed'),
    ]);
}
```

### 3. Session Configuration

**Current Configuration Issues:**
- `.env.example` has `SESSION_SECURE_COOKIE=true` which blocks cookies over HTTP in development
- `SESSION_SAME_SITE=strict` might be too restrictive

**Proposed Changes:**
- Set `SESSION_SECURE_COOKIE=false` for local development
- Keep `SESSION_SAME_SITE=lax` (already correct in `config/session.php`)
- Document environment-specific settings

## Data Models

No database schema changes required. This is purely a frontend-backend integration fix.

## Error Handling

### CSRF Token Errors (419)
**Before:** Silent failure or generic error
**After:** Proper validation error displayed to user

### Authentication Failures (401)
**Before:** JSON error response
**After:** Inertia validation error shown in form

### Session Expiration
**Handled by:** Laravel's session middleware automatically regenerates CSRF token on new session

## Testing Strategy

### Manual Testing
1. Clear browser cookies and cache
2. Navigate to login page
3. Submit valid credentials
4. Verify successful authentication without 419 error
5. Test with invalid credentials to verify error handling
6. Test "remember me" functionality

### Environment Testing
1. Test in local development (HTTP)
2. Verify session cookies are set correctly
3. Check CSRF token is included in requests
4. Validate session persistence across requests

### Edge Cases
1. Expired session during login attempt
2. Multiple tabs with different sessions
3. Browser with strict cookie policies
4. Login after logout

## Implementation Notes

### Environment Variables
Update `.env` for local development:
```env
SESSION_SECURE_COOKIE=false  # Allow cookies over HTTP in development
SESSION_SAME_SITE=lax        # Already set correctly
SESSION_ENCRYPT=false        # Optional: simplify debugging
```

### Axios Benefits
- Already configured globally in `bootstrap.js`
- Automatic CSRF token inclusion from meta tag
- Cookie handling with `withCredentials: true` already set
- Simpler API than fetch()
- Consistent with Laravel ecosystem
- No additional configuration needed in components

### Security Considerations
- Session regeneration after login prevents session fixation
- CSRF protection remains active
- Secure cookies enforced in production
- HTTP-only cookies prevent XSS attacks

## Migration Path

1. Update `.env.example` with correct development settings
2. Refactor Login Component to use Inertia forms
3. Update AuthController to return Inertia responses
4. Test thoroughly in development
5. Verify production configuration remains secure
