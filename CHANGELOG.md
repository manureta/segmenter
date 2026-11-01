# Changelog

## No publicado

- Fix: `symfony/mime` actualizado (parche) por CVE-2026-45070 (CRLF injection en headers de email).
- Fix: `doctrine/dbal` subido de `^2.10` a `^3.5` — Laravel 10 llama `createSchemaManager()`, método que no existe en DBAL 2.x; rompía migraciones con `renameColumn`.
- Fix: migración `seed_fuente_table` restablece `search_path` después de correr `fuente.sql` (el dump de `pg_dump` lo vacía para toda la sesión y rompía el registro interno de migraciones de Laravel).
- Agregado: entorno Docker de desarrollo (PHP 8.4, PostGIS, Redis, Vite) en `docker/` y `docker-compose.yml`.
- Decisión: migrar Laravel 10 → 12. Motivo: Laravel 10 está fuera de soporte de seguridad (EOL ~feb. 2025) y `composer audit` reporta vulnerabilidades sin parche posible dentro de la rama 10.x, entre ellas una crítica (bypass de autenticación en `TokenGuard`, CVE-2026-114509, parchada solo desde 12.68/13.29) y una alta (CRLF injection en la regla de validación de email, CVE-2026-48019, afecta todo el rango 10.x sin parche).
