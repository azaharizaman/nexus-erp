-- PostgreSQL initialization script for Laravel ERP development environment
-- This script sets up extensions and initial database configuration

-- Enable required PostgreSQL extensions for Laravel ERP
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";      -- UUID generation
CREATE EXTENSION IF NOT EXISTS "pgcrypto";       -- Cryptographic functions
CREATE EXTENSION IF NOT EXISTS "hstore";         -- Hstore data type
CREATE EXTENSION IF NOT EXISTS "ltree";          -- Hierarchical tree data
CREATE EXTENSION IF NOT EXISTS "intarray";       -- Integer array operators
CREATE EXTENSION IF NOT EXISTS "unaccent";       -- Remove accents from strings
CREATE EXTENSION IF NOT EXISTS "fuzzystrmatch"; -- Fuzzy string matching
CREATE EXTENSION IF NOT EXISTS "pg_trgm";        -- Full-text search support
CREATE EXTENSION IF NOT EXISTS "btree_gin";      -- GIN index for complex queries
CREATE EXTENSION IF NOT EXISTS "btree_gist";     -- GiST index for complex queries
CREATE EXTENSION IF NOT EXISTS "json";           -- JSON operations

-- Set default isolation level for transactions
ALTER DATABASE laravel_erp SET default_transaction_isolation = 'read committed';

-- Configure connection settings
ALTER DATABASE laravel_erp SET idle_in_transaction_session_timeout = '5min';
ALTER DATABASE laravel_erp SET statement_timeout = '30s';

-- Configure shared preload libraries for performance (if needed)
-- This requires PostgreSQL restart and should be done in postgresql.conf
-- ALTER SYSTEM SET shared_preload_libraries = 'pg_stat_statements';

-- Create schema for application if needed
CREATE SCHEMA IF NOT EXISTS app AUTHORIZATION erp_user;

-- Grant permissions
GRANT USAGE ON SCHEMA app TO erp_user;
GRANT ALL PRIVILEGES ON SCHEMA app TO erp_user;

-- Set default privileges for future objects
ALTER DEFAULT PRIVILEGES IN SCHEMA app GRANT ALL ON TABLES TO erp_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA app GRANT ALL ON SEQUENCES TO erp_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA app GRANT ALL ON FUNCTIONS TO erp_user;

-- Create a function to automatically update updated_at timestamps
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Grant execute permission to app user
GRANT EXECUTE ON FUNCTION update_updated_at_column() TO erp_user;

-- Enable statement logging for development (optional, impacts performance)
-- ALTER DATABASE laravel_erp SET log_statement = 'all';

-- Configure logging
ALTER DATABASE laravel_erp SET log_min_duration_statement = 5000;  -- Log queries taking > 5s

-- Comment on database
COMMENT ON DATABASE laravel_erp IS 'Laravel ERP development database';
COMMENT ON SCHEMA app IS 'Application schema for Laravel ERP';
