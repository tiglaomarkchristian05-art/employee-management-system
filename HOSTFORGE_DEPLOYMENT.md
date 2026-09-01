# HostForge deployment checklist

The application is built from the root `Dockerfile` and listens on container port `80`.

## Required environment variables

- `APP_ENV=production`
- `APP_DEBUG=0`
- `APP_URL=https://edcb.greatsolomonmpservices.com`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

For a brand-new empty database, also set private values of at least 12 characters for:

- `INITIAL_ADMIN_PASSWORD`
- `INITIAL_USER_PASSWORD`

Initial passwords are applied only when the users table is empty. Container restarts and later deployments do not overwrite changed passwords.

## Persistent storage

Configure persistent HostForge volumes for all of these container paths:

- `/var/www/html/public/uploads`
- `/var/www/html/assets/uploads`
- `/var/www/html/uploads`

The Docker image declares these paths as volumes, but the HostForge service must attach persistent storage to them. Without persistent storage, uploaded documents may be lost when a container is replaced.

## Database behavior

The startup command runs `scripts/init-database.php` before Apache. It creates missing schema objects, seeds only an empty users table, and applies idempotent workflow patches. It does not replace an existing populated database.

## Post-deployment checks

1. Confirm the service health check passes.
2. Open the HTTPS URL and sign in.
3. Verify Admin and Employee dashboards.
4. Upload and download a disposable test document.
5. Restart the service and confirm the uploaded file and database record remain.
6. Export a live SQL backup from **System → Database Backup**.
