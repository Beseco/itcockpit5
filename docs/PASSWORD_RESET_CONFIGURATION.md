# Password Reset Configuration

## Overview

The IT Cockpit v5.0 Core System includes a fully functional password reset system configured with Laravel Breeze. This document outlines the configuration and functionality.

## Configuration

### Email Settings (.env)

The following email settings are configured in the `.env` file:

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@itcockpit.local"
MAIL_FROM_NAME="${APP_NAME}"
```

**For Production:** Update these settings with your actual SMTP server details:
- `MAIL_HOST`: Your SMTP server hostname
- `MAIL_PORT`: SMTP port (typically 587 for TLS or 465 for SSL)
- `MAIL_USERNAME`: SMTP authentication username
- `MAIL_PASSWORD`: SMTP authentication password
- `MAIL_ENCRYPTION`: Use `tls` or `ssl` for secure connections
- `MAIL_FROM_ADDRESS`: Your organization's email address

### Token Expiration

Password reset tokens expire after **60 minutes** as configured in `config/auth.php`:

```php
'passwords' => [
    'users' => [
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60,
        'throttle' => 60,
    ],
],
```

## Routes

The following password reset routes are registered in `routes/auth.php`:

- `GET /forgot-password` - Display password reset request form
- `POST /forgot-password` - Send password reset link email
- `GET /reset-password/{token}` - Display password reset form
- `POST /reset-password` - Process password reset

## Controllers

### PasswordResetLinkController

Handles password reset link requests:
- Validates email address
- Generates secure token
- Sends reset link email via Laravel's notification system

### NewPasswordController

Handles password reset:
- Validates reset token
- Validates new password and confirmation
- Updates user password with bcrypt hashing
- Invalidates the reset token after use

## Views

- `resources/views/auth/forgot-password.blade.php` - Password reset request form
- `resources/views/auth/reset-password.blade.php` - Password reset form

## Security Features

1. **Secure Token Generation**: Cryptographically secure tokens are generated for each reset request
2. **Token Expiration**: Tokens automatically expire after 60 minutes
3. **Single Use Tokens**: Tokens are invalidated after successful password reset
4. **Rate Limiting**: Password reset requests are throttled to prevent abuse (60 seconds between requests)
5. **Password Hashing**: New passwords are hashed using bcrypt before storage

## Testing

Comprehensive tests are available in `tests/Feature/Auth/PasswordResetFunctionalityTest.php`:

Run tests with:
```bash
php artisan test --filter=PasswordResetFunctionalityTest
```

## Requirements Validated

This implementation validates the following requirements:

- **Requirement 1.6**: System provides password reset functionality via email
- **Requirement 13.1**: Password reset emails use Laravel's mail system
- **Requirement 13.2**: SMTP configuration for email delivery
- **Requirement 13.3**: Secure token generation on password reset request
- **Requirement 13.4**: Reset token included in password reset email
- **Requirement 13.5**: Password reset tokens expire after 60 minutes
- **Requirement 13.6**: Token validation before allowing password changes

## Development Testing

For local development, you can use [MailHog](https://github.com/mailhog/MailHog) or [Mailpit](https://github.com/axllent/mailpit) to capture and view password reset emails:

### Using Mailpit (Recommended)

1. Install Mailpit
2. Configure `.env`:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=127.0.0.1
   MAIL_PORT=1025
   ```
3. Access Mailpit UI at `http://localhost:8025`

### Using Log Driver (Alternative)

For simple testing, you can use the log driver:

```env
MAIL_MAILER=log
```

Emails will be written to `storage/logs/laravel.log`.
