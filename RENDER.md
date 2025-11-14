# Deploy on Render.com

This guide explains how to deploy the Viviurka Contábil Backend on Render.com.

## Prerequisites

- A Render.com account
- GitHub repository with the backend code
- PostgreSQL database (can be created via Render)

## Quick Start

### 1. Connect Repository

1. Go to [Render Dashboard](https://dashboard.render.com)
2. Click "New +" → "Web Service"
3. Connect your GitHub repository
4. Select the repository and branch

### 2. Configure Web Service

- **Name**: `viviurka-backend`
- **Environment**: `Docker`
- **Dockerfile Path**: `./Dockerfile`
- **Docker Context**: `.`
- **Docker Command**: `sh -c "php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"`
- **Plan**: Starter (or higher)

### 3. Environment Variables

Set the following environment variables in Render:

```env
APP_NAME="Viviurka Contábil"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_URL=https://your-app.onrender.com

DB_CONNECTION=pgsql
DB_HOST=your-postgres-host
DB_PORT=5432
DB_DATABASE=your-database-name
DB_USERNAME=your-database-user
DB_PASSWORD=your-database-password
DB_SSLMODE=require

QUEUE_CONNECTION=database

AUTO_MIGRATE=true
RENDER=true

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mail-username
MAIL_PASSWORD=your-mail-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@viviurka.com
MAIL_FROM_NAME="${APP_NAME}"

MERCADOPAGO_ACCESS_TOKEN=your-mercadopago-token
MERCADOPAGO_PUBLIC_KEY=your-mercadopago-key
MERCADOPAGO_PRODUCTION=true
```

### 4. Create PostgreSQL Database

1. Go to "New +" → "PostgreSQL"
2. Configure:
   - **Name**: `viviurka-postgres`
   - **Database**: `viviurka_contabil`
   - **User**: `viviurka_user`
   - **Plan**: Starter (or higher)
3. Copy the connection details and update environment variables

### 5. Create Queue Worker

1. Go to "New +" → "Background Worker"
2. Configure:
   - **Name**: `viviurka-queue`
   - **Environment**: `Docker`
   - **Dockerfile Path**: `./Dockerfile`
   - **Docker Context**: `.`
   - **Docker Command**: `php artisan queue:work --tries=3 --timeout=90 --sleep=3`
   - **Plan**: Starter (or higher)
3. Use the same environment variables as the web service

### 6. Create Scheduler Worker

1. Go to "New +" → "Background Worker"
2. Configure:
   - **Name**: `viviurka-scheduler`
   - **Environment**: `Docker`
   - **Dockerfile Path**: `./Dockerfile`
   - **Docker Context**: `.`
   - **Docker Command**: `sh -c "while true; do php artisan schedule:run --verbose --no-interaction & sleep 60; done"`
   - **Plan**: Starter (or higher)
3. Use the same environment variables as the web service

## Using render.yaml

Alternatively, you can use the `render.yaml` file for infrastructure as code:

1. Commit `render.yaml` to your repository
2. Go to Render Dashboard → "New +" → "Blueprint"
3. Connect your repository
4. Render will automatically create all services defined in `render.yaml`

## Environment Variables in Render

### Required Variables

- `APP_KEY`: Generate with `php artisan key:generate`
- `DB_HOST`: PostgreSQL host (provided by Render)
- `DB_DATABASE`: PostgreSQL database name
- `DB_USERNAME`: PostgreSQL username
- `DB_PASSWORD`: PostgreSQL password

### Optional Variables

- `AUTO_MIGRATE`: Set to `true` to run migrations on deploy
- `AUTO_SEED`: Set to `true` to run seeders on deploy
- `RENDER`: Set to `true` to enable Render-specific optimizations
- `APP_DEBUG`: Set to `false` for production

## Health Check

Render will use the `/up` endpoint as a health check. Make sure this endpoint is accessible.

## Database Migrations

Migrations will run automatically on deploy if `AUTO_MIGRATE=true` is set. The entrypoint script handles this.

## SSL/TLS

Render provides SSL/TLS certificates automatically for all web services. No additional configuration needed.

## Custom Domain

1. Go to your web service settings
2. Click "Add Custom Domain"
3. Follow the instructions to configure DNS

## Monitoring

Render provides built-in monitoring and logs for all services. Check the dashboard for:
- Service status
- Logs
- Metrics
- Alerts

## Troubleshooting

### Database Connection Issues

- Verify `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` are correct
- Check that the PostgreSQL service is running
- Verify network connectivity between services

### Migration Issues

- Check logs for migration errors
- Verify `AUTO_MIGRATE=true` is set
- Manually run migrations: `php artisan migrate --force`

### Queue Worker Issues

- Verify `QUEUE_CONNECTION=database` is set
- Check that the queue worker service is running
- Verify database connection

### Scheduler Issues

- Verify the scheduler worker service is running
- Check logs for scheduler errors
- Verify cron jobs are configured correctly

## Support

For issues with Render.com, check:
- [Render Documentation](https://render.com/docs)
- [Render Community](https://community.render.com)
- [Render Support](https://render.com/support)

