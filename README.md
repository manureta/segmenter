# Sistema de Integración Geoestadisticio (Proyecto Mandarina)
![Logo INDEC][logo]

Hola, bienvenide!

Aquí están las instrucciones para instalar el entorno de desarrollo y el de producción de este proyecto.
Ésta es la parte web[1](nacida del segmentador asistido para el censo de población, hogares y viviendas de la ronda 2020 de Argentina.)

Pensado como una interfaz para ejecutar el **Segmentador-core** y también como interfaz de carga de los datos mínimos necesarios para éste.

Preparando junto al core la información en la base de datos para el plugin de QGIS, **salida gráfica**.

Intentado implementar el almacenamiento relacional de la información geoestadística imaginado x JC llamado toto5.

Todo aporte es bienvenido y agradecido.

salud! M.

### Introducción
En este repositorio se encunetran 4 ramas principales.
- master  donde se integran los desarrollos 
- dev     la versión que usada en el servidor de integración.
- uat     versión de testing
- prd     versión para producción

- Clonar repositorio con el submódulo de Segmentacion-CORE
```bash
git clone https://github.com/manureta/segmenter.git --recurse-submodules
cd segmenter
```
Si el submódulo no se inició con `--recurse-submodules` en el clone:
```bash
git submodule init
git submodule update
```

## Desarrollo (Docker)

El entorno de desarrollo corre en contenedores (`docker-compose.yml` + `docker/`): PHP 8.4, PostgreSQL+PostGIS, Redis y un contenedor Node para Vite. No requiere instalar nada de esto en la máquina — solo Docker.

- Definir el entorno: `cp .env.example .env` (los valores de `DB_HOST`/`REDIS_HOST` se pisan automáticamente para apuntar a los contenedores `db`/`redis`, no hace falta tocarlos).

- Levantar la base y Redis, instalar dependencias (composer/npm se instalan solos la primera vez vía `docker/php/entrypoint.sh`) y generar la app key:
```bash
docker compose up -d db redis
docker compose run --rm app composer install
docker compose run --rm app php artisan key:generate
```

- Crear la estructura de la base y cargar datos:
```bash
docker compose run --rm app php artisan migrate
docker compose run --rm app php artisan db:seed
```
`db:seed` por sí solo deja la app sin Departamento/Fracción/Radio/topología
cargados (esos seeders vienen comentados en `DatabaseSeeder` porque cargan
el dump nacional completo). Para tener la jerarquía completa operable en
minutos con datos ficticios (provincia demo, código 98, con topología real
generada por `cargarTopologia()`):
```bash
docker compose run --rm app php artisan db:seed --class=DemoProvinciaSeeder
```
Para probar además el flujo real de carga de listado de viviendas
(`/segmentador`: C1 + cartografía → `listado_geo`), con columnas y
convención de lados/manzana reales, no inventadas:
```bash
docker compose run --rm app php artisan db:seed --class=DemoListadoSeeder
```
Esto carga directo a la base (sin pasar por el form). Para probar el form
en sí con un operario real, los mismos datos están descargables en
`app/developer_docs/demo/`: `c1_e98001001.csv` (listado, ya con el
delimitador `|` que espera el importador) y `shapefile_arc/` +
`shapefile_lab/` (cartografía, shp/shx/dbf/prj) — se puede subir esa
carpeta tal cual por `/segmentador`.

- Levantar la app, el worker de colas (Horizon) y Vite:
```bash
docker compose up -d app horizon node
```
- App: http://localhost:8000
- Horizon (dashboard de colas): http://localhost:8000/horizon
- Vite (HMR de assets): puerto 5173

- Para correr comandos sueltos (artisan, composer, tinker, tests) dentro del contenedor:
```bash
docker compose run --rm app php artisan test
docker compose run --rm app php artisan tinker
```

## Producción / instalación nativa

Para un servidor sin Docker (ver también `deploy_apache.md` para la configuración del virtualhost de Apache y `horizon.md`/`horizon.conf` para correr el worker de colas vía supervisor).

* PHP 8.4 o superior: php-mbstring php-dom php-zip php-gd php-pdo-pgsql php-cli php-curl php-intl php-bcmath php-pgsql composer
```bash
sudo apt install php-mbstring php-dom php-zip php-gd php-pdo-pgsql php-cli php-curl php-intl php-bcmath php-pgsql
curl -sS https://getcomposer.org/installer | php
```
* gdal (ogr2ogr), postgis, pgdbf, python3+psycopg2
```bash
sudo apt-get install gdal-bin postgresql-client pgdbf postgis python3 python3-psycopg2
```
* NodeJS (versión LTS actual, ver `.nvmrc`)
```bash
sudo apt install npm
npm install -g n
n lts
```

- Instalar dependencias
```bash
composer install
npm install
```

- Definir el entorno creando usando `.env` como template,
`cp .env.example .env`
editarlo para configurar la aplicación a que direccione a la BD

```
APP_URL=<url_del_servidor_donde_corre_la_aplicacion_laravel>:<puerto_del_servicio_de_la_aplicación>

DB_HOST=<url_del_servidor_de_DB_postgresql>
DB_DATABASE=<base_de_datos>
DB_USERNAME=<usuario_del_segmentador>
DB_PASSWORD=<clave_del_usuario_del_segmentador>
```

- Generar una app encryption key
```bash
php artisan key:generate
```

- Crear la base de datos mencionada en .env, conectarse, cargar postgis (y postgis_topology) y crear el usuario del segmentador
```bash
createdb <base_de_datos> -h <url_del_servidor_de_DB_postgresql> -U <algun_db_admin>
psql <base_de_datos> -h <url_del_servidor_de_DB_postgresql> -U <algun_db_admin> -c 'create extension postgis; create extension postgis_topology;'
```

- Crear la estructura de la base y cargar datos
```bash
php artisan migrate
php artisan db:seed
```

- Para configurar las tareas programadas de Laravel, agregar al cron (vía `crontab -e`)
```
* * * * * cd segmenter && php artisan schedule:run >> /dev/null 2>&1
```

- Para correr la aplicación (detrás de Apache, ver `deploy_apache.md`), o para probar suelto:
```bash
php artisan serve --host=<url_del_servidor_donde_corre_la_aplicacion_laravel> --port=<puerto_del_servicio_de_la_aplicación>
```

[logo]: https://www.indec.gob.ar/Images_WEBINDEC/Logo/Logo_Indec.png

