# Guía de Instalación del Entorno (Docker / Laravel Sail)

Este proyecto está configurado para ejecutarse mediante **Docker** utilizando **Laravel Sail**. Esto significa que no necesitas tener instalado PHP, Composer, ni MySQL en tu computadora, únicamente necesitas **Docker Desktop**.

Sigue estos pasos para levantar el proyecto desde cero en tu máquina local:

## Requisitos Previos

- Tener instalado [Docker Desktop](https://www.docker.com/products/docker-desktop) y que esté en ejecución.
- Tener instalado **Git** (y WSL2 si estás en Windows).

## Pasos de Instalación

### 1. Clonar el repositorio y entrar a la carpeta
Abre tu terminal y ejecuta:
```bash
git clone <URL_DEL_REPOSITORIO>
cd ATLAS-main
```

### 2. Copiar el archivo de entorno
Crea tu archivo de entorno local copiando el de ejemplo:
```bash
cp .env.example .env
```
*(Asegúrate de que las credenciales de la base de datos en `.env` coincidan con tu configuración, por defecto suele estar bien para Sail).*

### 3. Instalar las dependencias de Composer (vía Docker)
Como aún no tenemos la carpeta `vendor`, utilizaremos un contenedor temporal de Composer compatible con nuestra versión de PHP (8.3) para instalar las dependencias.

**En Windows (PowerShell):**
```powershell
docker run --rm -v "${PWD}:/var/www/html" -w /var/www/html laravelsail/php83-composer:latest composer install
```

**En Linux / Mac / WSL:**
```bash
docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php83-composer:latest composer install
```
*Nota: Es importante **no usar** el flag `--ignore-platform-reqs` para asegurar que las librerías respeten la versión de PHP 8.3.*

### 4. Levantar los contenedores de Docker
Una vez instalada la carpeta `vendor`, ya puedes levantar los servicios del proyecto:
```bash
./vendor/bin/sail up -d
```
*(La primera vez tomará algunos minutos mientras se descargan las imágenes necesarias).*

### 5. Generar la Key de la Aplicación
Con los contenedores corriendo, genera la clave segura para Laravel:
```bash
./vendor/bin/sail artisan key:generate
```

### 6. Ejecutar las Migraciones de la Base de Datos
Finalmente, crea las tablas en la base de datos ejecutando las migraciones (ya están corregidas y listas para MySQL):
```bash
./vendor/bin/sail artisan migrate
```

¡Listo! 🚀 El proyecto ya está corriendo y accesible. Puedes verlo abriendo [http://localhost](http://localhost) en tu navegador.

---

## Comandos Útiles del Día a Día

A partir de ahora, todas las interacciones con Artisan, NPM o Composer debes hacerlas a través de Sail para que se ejecuten dentro del contenedor:

- **Detener los contenedores:** `./vendor/bin/sail down`
- **Volver a levantarlos:** `./vendor/bin/sail up -d`
- **Ejecutar comandos Artisan:** `./vendor/bin/sail artisan <comando>`
- **Instalar paquetes NPM:** `./vendor/bin/sail npm install`
- **Compilar assets en desarrollo:** `./vendor/bin/sail npm run dev`
