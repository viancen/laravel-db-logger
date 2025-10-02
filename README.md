# Laravel Database Logger

Beautiful database logging with dashboard for Laravel 11+.

## Features

- Log to database with Monolog 3
- Beautiful dark-themed dashboard
- Filter by level, channel, user, IP, request ID
- Full-text search in message, context, and extra fields
- Exception stacktrace viewer in modal
- Real-time auto-refresh
- Export to JSON
- PostgreSQL & MySQL support

### Todo
- Logrotate, **beware**: table can grow very fast at this moment

## Installation
```bash
composer require viancen/laravel-db-logger
```

# Setup
### Publish everything
```bash
php artisan vendor:publish --provider="Viancen\LaravelDbLogger\DbLoggerServiceProvider"
```

### Or selectively:
```bash
php artisan vendor:publish --tag=db-logger-config
php artisan vendor:publish --tag=db-logger-migrations
php artisan vendor:publish --tag=db-logger-views
php artisan vendor:publish --tag=db-logger-assets
```

### Database migration:
```bash
php artisan migrate
```

### Configuration
Update your middleware and other configurations in `config/db-logger.php`:

