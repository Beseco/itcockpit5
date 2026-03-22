# IT Cockpit v5.0 - Setup Documentation

## Project Setup Complete

### Installed Components

✅ **Laravel 11** - Core framework (v11.48.0)
✅ **PHP 8.2.12** - Meets PHP 8.2+ requirement
✅ **Laravel Breeze** (v2.3.8) - Authentication scaffolding with Blade
✅ **Spatie Laravel-Permission** (v6.24.1) - RBAC package
✅ **Tailwind CSS** - Configured via Breeze
✅ **Alpine.js** - Configured via Breeze
✅ **Pest PHP** (v2.36.1) - Testing framework
✅ **Pest Laravel Plugin** (v2.4.0) - Laravel integration for Pest

### Database Configuration

The `.env` file has been configured with the following settings:

- **Application Name**: IT Cockpit v5.0
- **Database Connection**: MySQL
- **Database Name**: it_cockpit
- **Database Host**: 127.0.0.1
- **Database Port**: 3306
- **Database User**: root
- **Database Password**: (empty - update as needed)

### SMTP Configuration

- **Mail Driver**: smtp
- **Mail Host**: 127.0.0.1
- **Mail Port**: 1025 (for local testing with MailHog/Mailpit)
- **Mail From**: noreply@itcockpit.local

### Next Steps

1. **Create the database**:
   ```bash
   mysql -u root -p -e "CREATE DATABASE it_cockpit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

2. **Update database credentials** in `.env` if needed

3. **Run migrations** (after creating migration files):
   ```bash
   php artisan migrate
   ```
überklich 
4. **Run tests**:
   ```bash
   php artisan test
   # or
   ./vendor/bin/pest
   ```

5. **Start development server**:
   ```bash
   php artisan serve
   ```

6. **Compile assets** (in another terminal):
   ```bash
   npm run dev
   ```

### Testing Framework

Pest PHP is configured with:
- RefreshDatabase trait for database testing
- Laravel plugin for framework integration
- Architecture testing plugin for structural tests

### Requirements Validated

This setup validates the following requirements:
- ✅ Requirement 1.1: User Authentication (Laravel Breeze)
- ✅ Requirement 1.2: Password Hashing (Laravel bcrypt)
- ✅ Requirement 1.3: User Login (Laravel Breeze)
- ✅ Requirement 15.1: Application Name Configuration
- ✅ Requirement 15.2: SMTP Email Settings
- ✅ Requirement 15.3: Database Connection Parameters
- ✅ Requirement 15.4: Configuration in .env file

### File Structure

```
/
├── app/                    # Application code
├── bootstrap/              # Framework bootstrap
├── config/                 # Configuration files
├── database/               # Migrations, seeders, factories
├── public/                 # Public assets
├── resources/              # Views, CSS, JS
│   └── views/
│       ├── auth/          # Authentication views (Breeze)
│       ├── components/    # Blade components
│       ├── layouts/       # Layout templates
│       └── profile/       # User profile views
├── routes/                 # Route definitions
│   └── web.php           # Web routes (includes auth routes)
├── tests/                  # Test files
│   ├── Feature/           # Feature tests
│   ├── Unit/              # Unit tests
│   └── Pest.php          # Pest configuration
├── .env                    # Environment configuration
├── composer.json           # PHP dependencies
├── package.json            # Node dependencies
└── phpunit.xml            # PHPUnit configuration
```

### Breeze Authentication Routes

The following authentication routes are now available:
- `/register` - User registration
- `/login` - User login
- `/logout` - User logout
- `/forgot-password` - Password reset request
- `/reset-password` - Password reset form
- `/verify-email` - Email verification
- `/profile` - User profile management

### Development Tools

- **Laravel Pint**: Code style fixer (included)
- **Laravel Sail**: Docker development environment (included)
- **Laravel Tinker**: REPL for Laravel (included)
- **Pest**: Testing framework with Laravel integration

### Notes

- The project uses SQLite by default for testing (configured in phpunit.xml)
- MySQL is configured for development/production
- Tailwind CSS is compiled via Vite
- Alpine.js is included for interactive components
