# Docker Setup for Viviurka Contábil Backend

This document explains how to build and run the Laravel backend application using Docker with PostgreSQL.

## Prerequisites

- Docker (version 20.10 or higher)
- Docker Compose (version 2.0 or higher)

## Quick Start

### Development Environment

1. **Create .env file (if not exists):**
   ```bash
   cp .env.example .env
   # Edit .env with your configuration
   ```

2. **Set PostgreSQL environment variables in .env:**
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=postgres
   DB_PORT=5432
   DB_DATABASE=viviurka_contabil
   DB_USERNAME=postgres
   DB_PASSWORD=postgres
   ```

3. **Build and start containers:**
   ```bash
   docker-compose up -d --build
   ```

4. **Generate application key (if not exists):**
   ```bash
   docker-compose exec app php artisan key:generate
   ```

5. **Run migrations and seeders:**
   ```bash
   docker-compose exec app php artisan migrate --force
   docker-compose exec app php artisan db:seed --force
   ```

6. **Access the application:**
   - API: http://localhost:8000
   - Health check: http://localhost:8000/up
   - API Documentation: http://localhost:8000/api

### Production Environment

1. **Create .env file with production settings:**
   ```bash
   cp .env.example .env
   # Edit .env with production configuration
   ```

2. **Set PostgreSQL environment variables:**
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=postgres
   DB_PORT=5432
   DB_DATABASE=viviurka_contabil
   DB_USERNAME=postgres
   DB_PASSWORD=your-secure-password
   APP_ENV=production
   APP_DEBUG=false
   ```

3. **Build production image:**
   ```bash
   docker-compose -f docker-compose.prod.yml build
   ```

4. **Start production containers:**
   ```bash
   docker-compose -f docker-compose.prod.yml up -d
   ```

5. **Access the application:**
   - Through Nginx: http://localhost (port 80)
   - Direct PHP-FPM: port 9000 (internal only)

## Docker Services

### Development (`docker-compose.yml`)

- **postgres**: PostgreSQL database (port 5432)
  - Database: `viviurka_contabil`
  - User: `postgres`
  - Password: `postgres` (change in production)

- **app**: Main Laravel application (PHP 8.2 FPM)
  - Port: 8000
  - Runs `php artisan serve`

- **queue**: Queue worker for background jobs
  - Runs `php artisan queue:work`

- **scheduler**: Laravel scheduler for cron jobs
  - Runs `php artisan schedule:run` every minute

### Production (`docker-compose.prod.yml`)

- **postgres**: PostgreSQL database (port 5432)
  - Database: `viviurka_contabil`
  - User: `postgres`
  - Password: Set via environment variable

- **app**: Main Laravel application (PHP 8.2 FPM)
  - Port: 9000 (internal)
  - Optimized for production

- **nginx**: Nginx web server
  - Ports: 80, 443
  - Serves the Laravel application

- **queue**: Queue worker for background jobs
  - Runs `php artisan queue:work`

- **scheduler**: Laravel scheduler for cron jobs
  - Runs `php artisan schedule:run` every minute

## Common Commands

### Access container shell:
```bash
docker-compose exec app sh
```

### Access PostgreSQL:
```bash
docker-compose exec postgres psql -U postgres -d viviurka_contabil
```

### Run Artisan commands:
```bash
docker-compose exec app php artisan [command]
```

### View logs:
```bash
docker-compose logs -f app
docker-compose logs -f postgres
docker-compose logs -f queue
docker-compose logs -f scheduler
```

### Clear cache:
```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

### Run tests:
```bash
docker-compose exec app php artisan test
```

### Stop containers:
```bash
docker-compose down
```

### Stop and remove volumes:
```bash
docker-compose down -v
```

### Backup database:
```bash
docker-compose exec postgres pg_dump -U postgres viviurka_contabil > backup.sql
```

### Restore database:
```bash
docker-compose exec -T postgres psql -U postgres viviurka_contabil < backup.sql
```

## Environment Variables

Create a `.env` file in the root directory with the following variables:

```env
APP_NAME="Viviurka Contábil"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=viviurka_contabil
DB_USERNAME=postgres
DB_PASSWORD=postgres
DB_SSLMODE=prefer

QUEUE_CONNECTION=database

MAIL_MAILER=log
MAIL_HOST=localhost
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

MERCADOPAGO_ACCESS_TOKEN=
MERCADOPAGO_PUBLIC_KEY=
MERCADOPAGO_PRODUCTION=false
```

## Building Custom Images

### Development:
```bash
docker build -f Dockerfile.dev -t viviurka-backend:dev .
```

### Production:
```bash
docker build -f Dockerfile -t viviurka-backend:prod --target production .
```

## Auto Migration and Seeding

The production Dockerfile includes an entrypoint script that can automatically run migrations and seeders on container startup. To enable this, set the following environment variables in your `.env` file:

```env
AUTO_MIGRATE=true
AUTO_SEED=true
```

**Note:** By default, `AUTO_MIGRATE` and `AUTO_SEED` are set to `false` to prevent unintended database changes. Only enable these in controlled environments or when deploying for the first time.

## Troubleshooting

### Permission Issues
If you encounter permission issues with storage or cache directories:
```bash
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Database Connection Issues
If you can't connect to PostgreSQL:
```bash
# Check if PostgreSQL is running
docker-compose ps postgres

# Check PostgreSQL logs
docker-compose logs postgres

# Test connection
docker-compose exec app php artisan db:show
```

### Database Migration Issues
If migrations fail:
```bash
# Check database connection
docker-compose exec app php artisan db:show

# Run migrations manually
docker-compose exec app php artisan migrate --force

# Check migration status
docker-compose exec app php artisan migrate:status
```

### Clear All Cache
```bash
docker-compose exec app php artisan optimize:clear
```

### Restart Services
```bash
docker-compose restart
```

### Reset Database
```bash
# Drop all tables and re-run migrations
docker-compose exec app php artisan migrate:fresh --force

# Drop all tables, re-run migrations, and seed
docker-compose exec app php artisan migrate:fresh --seed --force
```

## Notes

- The development setup uses volume mounting for hot reloading
- Production setup uses optimized images without development dependencies
- Queue worker and scheduler run in separate containers
- All containers share the same network for communication
- Storage is persisted in volumes
- PostgreSQL data is persisted in a named volume
- The entrypoint script automatically handles database setup, key generation, and cache optimization
- PostgreSQL health checks ensure the database is ready before starting the application

## Health Check

The application includes a health check endpoint at `/up` that can be used to verify the application is running correctly.

## Running the Scheduler

The Laravel scheduler is automatically running in the `scheduler` container, which executes `php artisan schedule:run` every minute. This handles:
- Monthly payment generation
- Payment reminders
- Expired charges cleanup

## Security

For production:
- Set `APP_DEBUG=false`
- Use strong `APP_KEY`
- Configure proper CORS settings
- Use HTTPS with SSL certificates
- Set up proper firewall rules
- Regularly update Docker images
- Keep `AUTO_MIGRATE` and `AUTO_SEED` disabled unless necessary
- Use environment variables for sensitive data
- Never commit `.env` files to version control
- Use strong PostgreSQL passwords
- Enable SSL for PostgreSQL connections (`DB_SSLMODE=require`)

## Deploy on Render.com

See [RENDER.md](./RENDER.md) for instructions on deploying to Render.com.

## PostgreSQL Specific Notes

- PostgreSQL 16 is used in the Docker setup
- The database is configured with UTF-8 encoding
- SSL mode is set to `prefer` by default (change to `require` for production)
- Database backups should be performed regularly
- Use connection pooling for high-traffic applications
- Monitor database performance and optimize queries as needed
