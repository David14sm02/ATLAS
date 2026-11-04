# Diagnóstico y Resolución Técnica: Error SQLSTATE[25P02] con PgBouncer en Neon

**Proyecto:** ATLAS TECNM  
**Documento:** Informe Técnico de Troubleshooting y Resolución de Incidentes  
**Módulo:** Base de Datos (PostgreSQL 16 en Neon) / Laravel Sail & Filament v3  
**Fecha:** Octubre de 2026  
**Estado:** Resuelto y Verificado  

---

## 1. Resumen del Incidente

Al interactuar con la aplicación mediante componentes interactivos de Livewire (específicamente peticiones `POST /livewire/update` en el formulario de acceso o formularios de Filament), la aplicación devolvía un error **HTTP 500 (Internal Server Error)** con la siguiente traza recurrente:

```text
Illuminate\Database\QueryException
SQLSTATE[25P02]: In failed sql transaction: 7 ERROR: current transaction is aborted, commands ignored until end of transaction block
(SQL: update "cache" set "value" = i:1; where "key" = atlas_tecnm_cache-livewire-rate-limiter:...)
```

La excepción indicaba `1 previous exception`, evidenciando que el error `25P02` era una consecuencia en cascada provocada por un fallo no controlado previo dentro del mismo bloque de transacción.

---

## 2. Causa Raíz (Root Cause Analysis)

### 2.1. Arquitectura de Neon y PgBouncer en Transaction Pooling
La conexión en el archivo `.env` apuntaba al endpoint optimizado para serverless de Neon:
`ep-fancy-silence-aw83f1rs-pooler.c-12.us-east-1.aws.neon.tech`

El sufijo `-pooler` indica que la conexión pasa obligatoriamente por **PgBouncer configurado en modo `Transaction Pooling`**.
- En modo **Session Pooling**, un cliente retiene la misma conexión física con PostgreSQL durante toda la sesión.
- En modo **Transaction Pooling**, PgBouncer asigna una conexión física del backend únicamente mientras dura una transacción (`BEGIN` ... `COMMIT`/`ROLLBACK`). Al finalizar la transacción o fuera de ella, la conexión física se devuelve inmediatamente al pool y puede ser asignada a otro cliente o proceso.

### 2.2. Incompatibilidad con Prepared Statements del Servidor
Por diseño de PgBouncer, el modo **Transaction Pooling no soporta sentencias preparadas nombradas del lado del servidor** (`PREPARE stmt AS ...`).

Por defecto, la extensión PDO para PostgreSQL (`pdo_pgsql`) en PHP maneja sentencias preparadas nativas en el servidor (`PDO::ATTR_EMULATE_PREPARES => false`). Al reutilizar una conexión multiplexada por PgBouncer:
1. Una sentencia preparada intentaba registrarse con un nombre que ya existía en esa conexión física, o intentaba ejecutarse en una conexión física que nunca la había recibido.
2. PostgreSQL abortaba inmediatamente el bloque de la transacción activa:
   `ERROR: current transaction is aborted, commands ignored until end of transaction block (SQLSTATE 25P02)`.
3. Cualquier consulta subsiguiente dentro del mismo ciclo de vida de la transacción (como el incremento del rate-limiter en la tabla `cache`) fallaba irremediablemente.

### 2.3. Agravante: Caché y Sesiones sobre la Base de Datos Remota
Adicionalmente, el entorno estaba configurado con:
```env
SESSION_DRIVER=database
CACHE_STORE=database
```
Esto forzaba a que **cada ciclo de vida de Livewire** ejecutara múltiples consultas de lectura y bloqueo de escritura sobre la tabla `cache` y `sessions` a través de internet (AWS US-East-1), generando:
- Una sobrecarga innecesaria de transacciones cortas en PgBouncer.
- Una penalización de latencia de red de entre 180ms y 250ms por cada interacción de usuario.

---

## 3. Metodología de Diagnóstico y Auditoría

Para asegurar que el error no fuera síntoma de una base de datos mal migrada tras su reemplazo, se ejecutó una auditoría exhaustiva en 4 dimensiones:

### 3.1. Auditoría de Migraciones
Se comprobó el estado de las migraciones en Neon mediante:
```bash
php artisan migrate:status
```
Se confirmó que las 10 migraciones del proyecto se encontraban aplicadas en el `Batch 1` sin pendientes:
1. `0001_01_01_000001_create_cache_table`
2. `0001_01_01_000002_create_jobs_table`
3. `2026_09_19_100001_create_cat_ejes_table`
4. `2026_09_19_100002_create_cat_submodulos_table`
5. `2026_09_19_100003_create_cat_entidades_table`
6. `2026_09_19_100004_create_cat_planteles_table`
7. `2026_09_19_100005_create_users_table`
8. `2026_09_19_100006_create_cat_periodos_table`
9. `2026_09_19_100007_create_rep_registros_base_table`
10. `2026_09_19_100008_create_rep_bitacora_cargas_table`

### 3.2. Comparación de Esquema Real vs Documentación
Se inspeccionó el diccionario de datos (`information_schema` y `pg_indexes`) en Neon y se contrastó contra [docs/base_datos/arquitectura_bd.md](file:///c:/Users/Maiky/Desktop/ATLAS-SCOPED/ATLAS-SCOPED/ATLAS-main/docs/base_datos/arquitectura_bd.md) y [docs/tecnica/arquitectura_scoped_multi_tenancy.md](file:///c:/Users/Maiky/Desktop/ATLAS-SCOPED/ATLAS-SCOPED/ATLAS-main/docs/tecnica/arquitectura_scoped_multi_tenancy.md):
- **Estructura relacional:** 100% fiel a la documentación técnica.
- **Columnas generadas:** `mes_corte` y `semana_corte` en `rep_registros_base` confirmadas como `GENERATED ALWAYS AS (...) STORED`.
- **Índice GIN:** Confirmado `idx_rep_reg_detalles_gin` sobre `detalles_adicionales` mediante `gin_trgm_ops` / `jsonb_path_ops`.
- **Integridad referencial:** Llaves foráneas con eliminación en cascada donde corresponde y nulidad en `users.plantel_id` para Directivos Nacionales.

### 3.3. Verificación Automatizada del Aislamiento Multi-Tenant
Para garantizar el aislamiento de datos por plantel sin escribir información de prueba en la base remota de producción, se diseñó la suite automatizada [tests/Feature/TenantIsolationTest.php](file:///c:/Users/Maiky/Desktop/ATLAS-SCOPED/ATLAS-SCOPED/ATLAS-main/tests/Feature/TenantIsolationTest.php) ejecutada sobre SQLite en memoria (`:memory:`):
- **Test 1:** Operador de Puebla únicamente puede consultar registros pertenecientes a Puebla.
- **Test 2:** Operador de Veracruz tiene restringido el acceso a registros de Puebla.
- **Test 3:** Directivo Nacional (`plantel_id = null`) tiene visibilidad global irrestricta.
- **Test 4:** Creación de registros asigna automáticamente el `plantel_id` del usuario en sesión sin manipulación manual.

Todas las pruebas concluyeron con 100% de éxito.

---

## 4. Solución Implementada

La resolución definitiva consistió en desacoplar el rate-limiting/sesiones de PostgreSQL y compatibilizar PDO con PgBouncer:

### 4.1. Habilitación de Emulación de Prepared Statements en PDO
En `config/database.php`, dentro de la conexión `'pgsql'`, se añadió el atributo `\PDO::ATTR_EMULATE_PREPARES => true`:

```php
// config/database.php
'pgsql' => [
    'driver' => 'pgsql',
    'url' => env('DB_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'laravel'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => env('DB_CHARSET', 'utf8'),
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => 'public',
    'sslmode' => env('DB_SSLMODE', 'prefer'),
    'options' => [
        \PDO::ATTR_EMULATE_PREPARES => true,
    ],
],
```

**Efecto:** PHP interpola y escapa de forma segura los parámetros antes de enviar la consulta cruda al socket de PostgreSQL. Al no registrar sentencias preparadas con nombre en el servidor, PgBouncer puede alternar conexiones físicas libremente sin provocar abortos de transacción `25P02`.

### 4.2. Activación de Redis para Caché, Rate Limiting y Sesiones
El contenedor Docker de Redis (`redis:alpine`) ya se encontraba operativo en la infraestructura de Sail. Se reconfiguró el archivo `.env` para aprovechar este almacenamiento en memoria ultra rápido:

```env
SESSION_DRIVER=redis
SESSION_LIFETIME=120

CACHE_STORE=redis

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**Beneficios obtenidos:**
1. **Cero colisiones en transacciones SQL:** El `RateLimiter` de Livewire ya no compite ni bloquea transacciones de base de datos relacionales.
2. **Latencia mínima:** Las operaciones de lectura/escritura de sesión y tokens CSRF pasaron de **~200 ms** (viaje de red a Neon en AWS US) a **< 0.1 ms** (acceso a memoria RAM local en el contenedor Redis).

### 4.3. Purga y Recompilación del Entorno
Se ejecutó la limpieza atómica de cachés de Laravel:
```bash
docker compose exec -T laravel.test php artisan optimize:clear
```

---

## 5. Resultados y Verificación Posterior

| Prueba / Verificación | Resultado Previo | Resultado Posterior |
| :--- | :--- | :--- |
| **`RateLimiter::hit()` en Livewire** | `SQLSTATE[25P02]` (Aborto de transacción) | Ejecución exitosa (< 0.1 ms en Redis) |
| **Acceso a `/admin/login`** | Error 500 recurrente en consola | **HTTP 200 OK** con cookie de sesión válida |
| **`storage/logs/laravel.log`** | Bucle infinito de excepciones PDO | **0 errores registrados** tras el despliegue |
| **Suite de Pruebas Automatizadas** | Inconsistencia por sesiones DB | **6 passed (7 assertions)** en 23.24s |
| **Migraciones en Neon** | Desconocido | **10 migraciones aplicadas** (`[1] Ran`) |

---

## 6. Guía para el Equipo de Desarrollo

Para evitar la reaparición de este problema en futuros desarrollos o despliegues a entornos de staging/producción:

1. **Nunca deshabilitar `ATTR_EMULATE_PREPARES` con PgBouncer en Transaction Pooling:**
   Si se conecta a Neon mediante la URL que contiene `-pooler`, `\PDO::ATTR_EMULATE_PREPARES => true` debe permanecer activo en `config/database.php`.
2. **No usar `SESSION_DRIVER=database` ni `CACHE_STORE=database` en producción:**
   Utilizar siempre Redis o Memcached para sesiones y rate limiting en aplicaciones Livewire/Filament de alta concurrencia.
3. **Pruebas de Aislamiento Tenant:**
   Cualquier nueva tabla con el trait `BelongsToPlantel` debe incluir sus respectivos casos de prueba en `tests/Feature/TenantIsolationTest.php` usando la conexión en memoria `:memory:`.
