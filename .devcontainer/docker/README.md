# Docker Configuration Files

This directory contains all Docker-related configuration files for the Laravel ERP development environment.

## Files

### `Dockerfile`
**Location:** `/workspaces/laravel-erp/Dockerfile` (project root)  
**Purpose:** Defines the PHP 8.3 Alpine image with all project dependencies

**Key Features:**
- PHP 8.3 with Alpine Linux
- All PostgreSQL, Redis, and development extensions
- Xdebug for debugging
- Composer and Laravel Installer
- Node.js and npm
- Development utilities (git, vim, nano, htop, supervisor)
- 2GB memory limit (development)
- Health checks implemented

**Usage:** `docker-compose build` (builds automatically)

---

### `php.ini`
**Location:** `/workspaces/laravel-erp/docker/php.ini`  
**Purpose:** PHP configuration for development environment

**Sections:**
1. **Core Settings** - Memory, execution time, upload limits
2. **Error Reporting** - Development-friendly error display
3. **Opcache** - PHP bytecode caching (256MB buffer)
4. **Session** - Redis-based sessions
5. **PostgreSQL** - Connection settings
6. **MySQLi** - MySQL compatibility settings
7. **Xdebug** - IDE debugging configuration
8. **Redis** - Redis client settings
9. **Internationalization** - Locale and character encoding

**Custom Settings:**
- Memory limit: 2GB (large for complex queries)
- Max input vars: 5000 (for large forms)
- Opcache validation: On (auto-reload in development)
- Xdebug mode: develop, debug, coverage
- Log slow queries: > 5 seconds

**Mounted as:** `/usr/local/etc/php/conf.d/99-laravel.ini` in container

---

### `postgres-init.sql`
**Location:** `/workspaces/laravel-erp/docker/postgres-init.sql`  
**Purpose:** PostgreSQL initialization script (runs on first startup)

**Installs Extensions:**
1. `uuid-ossp` - UUID generation
2. `pgcrypto` - Cryptographic functions
3. `hstore` - Key-value storage
4. `ltree` - Hierarchical tree data
5. `intarray` - Integer array operators
6. `unaccent` - Remove accents from strings
7. `fuzzystrmatch` - Fuzzy string matching
8. `pg_trgm` - Full-text search support
9. `btree_gin` - GIN index type
10. `btree_gist` - GiST index type
11. `json` - JSON operations

**Configures:**
- Database isolation level
- Transaction timeouts
- Statement timeout (30 seconds)
- Slow query logging (> 5 seconds)
- Application schema and permissions
- Automatic timestamp update function
- Default privileges for new objects

**Auto-executed:** On PostgreSQL first run

---

### `pgadmin-servers.json`
**Location:** `/workspaces/laravel-erp/docker/pgadmin-servers.json`  
**Purpose:** Pre-configured database connections for PgAdmin

**Configured Servers:**
1. **Development (Docker)**
   - Host: `postgres` (Docker service)
   - Port: 5432
   - Database: `laravel_erp`
   - User: `erp_user`
   - Password: `erp_password`

2. **Local (External)**
   - Host: `localhost`
   - Port: 5432
   - For connecting to external PostgreSQL

**Benefit:** Automatic server configuration on PgAdmin startup (no manual setup needed)

**Mounted as:** `/pgadmin4/servers.json` in container

---

### `.bashrc`
**Location:** `/workspaces/laravel-erp/docker/.bashrc`  
**Purpose:** Bash shell profile for container

**Features:**
- Color output enabled
- Helpful command aliases:
  - `artisan` → `php artisan`
  - `composer` → `composer`
  - `pest` → `./vendor/bin/pest`
  - `pint` → `./vendor/bin/pint`
  - Laravel shortcuts: `migrate`, `seed`, `tinker`, `serve`, etc.

- Startup information:
  - All service URLs displayed
  - Quick command reference
  - Documentation links

- Environment variables:
  - `APP_DIR`, `DB_HOST`, `REDIS_HOST`, `MEILISEARCH_HOST`

- Auto-navigation to `/workspace`

**Mounted as:** `/home/www-data/.bashrc` in container

---

## How These Files Are Used

### Build Process
1. Docker reads `Dockerfile` from project root
2. Builds PHP 8.3 Alpine image with all extensions
3. Mounts `docker/php.ini` as PHP configuration
4. Creates container from image

### Runtime
1. `docker-compose.yml` orchestrates all services
2. PostgreSQL container:
   - Mounts `docker/postgres-init.sql` as initialization script
   - Runs automatically on first startup
   - Mounts `docker/pgadmin-servers.json` for PgAdmin
3. App container:
   - Uses `docker/php.ini` for PHP settings
   - Uses `docker/.bashrc` for shell profile
   - Mounts entire project at `/workspace`

### Development
- Modify `docker/php.ini` for PHP settings (rebuild with `make rebuild`)
- Modify `postgres-init.sql` for database initialization (delete volume and restart)
- Modify `.bashrc` for shell aliases and startup info
- Modify `pgadmin-servers.json` to change database connections

## Rebuilding After Changes

```bash
# After modifying php.ini or Dockerfile
make rebuild

# After modifying postgres-init.sql
docker-compose down -v  # Remove data volume
docker-compose up -d    # Restart with fresh database

# After modifying .bashrc or pgadmin-servers.json
docker-compose restart app
docker-compose restart pgadmin
```

## Docker Compose Configuration

See `docker-compose.yml` in project root for complete service definition:
- Service definitions (app, postgres, redis, meilisearch, pgadmin, mailhog, redis-commander)
- Environment variables
- Volumes
- Network configuration
- Health checks
- Port mappings

## Makefile Commands

See `Makefile` in project root for convenient shortcuts using these Docker configs:

```bash
make start              # Start all services
make stop               # Stop all services
make build              # Build Docker image
make logs               # View all logs
make bash               # Enter app container
make migrate            # Run migrations
make test               # Run tests
make format             # Format code
```

## Additional Documentation

- **DOCKER_SETUP.md** - Complete detailed setup guide (500+ lines)
- **DOCKER_QUICK_REFERENCE.md** - Quick lookup for common tasks
- **DOCKER_IMPLEMENTATION_SUMMARY.md** - Complete implementation overview
- **Makefile** - Development shortcuts (recommended usage method)

## Support

For detailed information:
1. See `DOCKER_SETUP.md` for comprehensive guide
2. See `DOCKER_QUICK_REFERENCE.md` for quick lookup
3. See `Makefile` for available commands
4. Check service logs: `docker-compose logs <service>`

---

**Last Updated:** November 12, 2025  
**Version:** 1.0
