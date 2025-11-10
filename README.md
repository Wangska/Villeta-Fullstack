## Simple PHP Notes App (No Users)

A minimal notes app using PHP, HTML, CSS and MySQL. Stores notes with title, content, created/updated timestamps. No authentication.

### Database Schema

Run the SQL in `schema.sql` on your MySQL (e.g., via TablePlus).

### Environment Variables (Coolify)

The app reads standard variables:

- `DB_CONNECTION` (unused, informational)
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

These match your Coolify configuration. No `.env` file is required in the container.

### Local Development (optional)

1. Ensure MySQL is running and create the schema/table using `schema.sql`.
2. Export environment variables:

```bash
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_DATABASE=default
export DB_USERNAME=root
export DB_PASSWORD=yourpassword
```

3. Run a PHP dev server:

```bash
php -S 0.0.0.0:8080
```

Open `http://localhost:8080`.

### Docker / Coolify

This repo includes a `Dockerfile` based on `php:8.2-apache` with `pdo_mysql` installed.

- Build locally:

```bash
docker build -t notes-php .
docker run -p 8080:80 \
  -e DB_HOST=host.docker.internal \
  -e DB_PORT=3306 \
  -e DB_DATABASE=default \
  -e DB_USERNAME=root \
  -e DB_PASSWORD=yourpassword \
  notes-php
```

Open `http://localhost:8080`.

In Coolify, set the environment variables and deploy using this Dockerfile. No volume persistence is required for the app; data is persisted in your MySQL.


