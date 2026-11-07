-- One-time setup of roles, database and grants. Run as a PostgreSQL superuser:
--   psql -d postgres -v db=agente_atc -v owner_pw="'...'" -v admin_pw="'...'" -v app_pw="'...'" -f database/sql/bootstrap.sql
-- Repeat with -v db=agente_atc_test for the test database (roles are reused).
--
--   agente_owner  owns the schema; only runs migrations (DB_OWNER_*)
--   agente_admin  BYPASSRLS; superadmin panel and cross-tenant jobs (DB_ADMIN_*)
--   agente_app    runtime role, subject to Row Level Security (DB_USERNAME)

SELECT format('CREATE ROLE agente_owner LOGIN NOBYPASSRLS PASSWORD %s', :owner_pw)
WHERE NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'agente_owner')\gexec
SELECT format('CREATE ROLE agente_admin LOGIN BYPASSRLS PASSWORD %s', :admin_pw)
WHERE NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'agente_admin')\gexec
SELECT format('CREATE ROLE agente_app LOGIN NOBYPASSRLS PASSWORD %s', :app_pw)
WHERE NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'agente_app')\gexec

SELECT format('CREATE DATABASE %I OWNER agente_owner', :'db')
WHERE NOT EXISTS (SELECT 1 FROM pg_database WHERE datname = :'db')\gexec

\connect :db

CREATE EXTENSION IF NOT EXISTS vector;
ALTER SCHEMA public OWNER TO agente_owner;
REVOKE CREATE ON SCHEMA public FROM PUBLIC;
GRANT USAGE ON SCHEMA public TO agente_app, agente_admin;
ALTER DEFAULT PRIVILEGES FOR ROLE agente_owner IN SCHEMA public
    GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO agente_app, agente_admin;
ALTER DEFAULT PRIVILEGES FOR ROLE agente_owner IN SCHEMA public
    GRANT USAGE, SELECT ON SEQUENCES TO agente_app, agente_admin;
