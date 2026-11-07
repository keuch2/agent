# Agente IA de ATC

Plataforma SaaS multiempresa de Mister Co. que atiende a los clientes de cada empresa por WhatsApp, Messenger e Instagram con IA y deriva a asesores humanos.

Laravel 13 · PHP 8.4 · React + TypeScript (Inertia) · PostgreSQL 16 + pgvector · Redis.

## Desarrollo local (Homebrew)

```sh
brew install postgresql@16 redis
brew services start postgresql@16 && brew services start redis
```

pgvector: la fórmula de Homebrew solo compila contra PostgreSQL 17/18. Para la 16:

```sh
curl -sL https://github.com/pgvector/pgvector/archive/refs/tags/v0.8.7.tar.gz | tar xz
cd pgvector-0.8.7 && PG_CONFIG=/opt/homebrew/opt/postgresql@16/bin/pg_config make && PG_CONFIG=/opt/homebrew/opt/postgresql@16/bin/pg_config make install
```

Base de datos y roles (local: contraseña = nombre del rol):

```sh
PSQL=/opt/homebrew/opt/postgresql@16/bin/psql
for db in agente_atc agente_atc_test; do
  $PSQL -d postgres -v db=$db -v owner_pw="'agente_owner'" -v admin_pw="'agente_admin'" -v app_pw="'agente_app'" -f database/sql/bootstrap.sql
done
```

Aplicación:

```sh
composer install && npm install
cp .env.example .env && php artisan key:generate   # completar DB_*_PASSWORD
php artisan migrate --database=pgsql_owner
php artisan db:seed --database=pgsql_admin           # planes, roles y datos demo
npm run build
```

Se sirve desde la raíz de su propio host (Inertia y Wayfinder generan URLs absolutas): VirtualHost `agente-atc.localhost` en `/opt/homebrew/etc/httpd/extra/httpd-agente-atc.conf`.

Usuarios demo (contraseña `password`): `superadmin@misterco.test` (debe activar 2FA), `admin@demo.test`, `asesor@demo.test`, `admin@otra.test`.

## Multiempresa

- Una sola base; toda tabla de negocio tiene `tenant_id` y usa el trait `BelongsToTenant`.
- `TenantContext` guarda la empresa activa del request o job. Sin empresa, las consultas fallan (`MissingTenantContext`) salvo en la conexión de plataforma.
- Row Level Security de PostgreSQL como segunda barrera: el rol `agente_app` solo ve filas con `tenant_id = app_current_tenant_id()`. Toda tabla nueva con `tenant_id` debe llamar `RowLevelSecurity::enableForTable()` en su migración; un test lo verifica.
- `users` es la excepción: se lee antes de conocer la empresa (login, 2FA), así que la aíslan el scope y las policies.
- Los jobs encolados heredan la empresa por Laravel Context.

## Comandos

```sh
./vendor/bin/pest            # tests (PostgreSQL agente_atc_test)
composer lint                # Pint
./vendor/bin/phpstan analyse
npm run types:check && npm run check
```
