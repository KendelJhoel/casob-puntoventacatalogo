# casob-puntoventacatalogo

Sistema de **Punto de Venta y Catálogo Web** desarrollado en PHP con Programación Orientada a Objetos. Gestiona un catálogo de productos y servicios, registra ventas desde el navegador y genera tickets históricos consultables. También conserva la interfaz de consola de la Fase 1.

> **Caso B** — Ejercicio académico (Periodo 2, 2026). Integrantes: Miguel, Diego, Kendel Jhoel, Rodolfo Rivas.

---

## Características

- 🌐 Interfaz web completa: catálogo, alta, edición, borrado, venta, ticket y reporte
- 🛒 Punto de venta con validación de stock y transacción MySQL
- 📦 **Productos Físicos** — con control de stock
- 💻 **Productos Digitales** — sin stock, con enlace de descarga
- 🛠️ **Servicios** — agendables, sin stock
- 🧾 Ticket de compra histórico (no cambia si se edita el catálogo)
- 🔒 CSRF, PRG, escape HTML y consultas preparadas
- 🖥️ Versión de consola interactiva conservada (`php app.php`)

---

## Arranque con Docker Compose (recomendado para el equipo)

Instala Git y Docker Desktop con Docker Compose v2, abre Docker Desktop y utiliza contenedores Linux. En Linux también sirve Docker Engine con el plugin Compose. No necesitas instalar PHP, Composer ni MySQL en tu PC para esta opción.

```bash
git clone https://github.com/KendelJhoel/casob-puntoventacatalogo.git
cd casob-puntoventacatalogo
cp .env.example .env
```

En PowerShell, el último comando también puede escribirse `Copy-Item .env.example .env`. Edita `.env` y elige contraseñas distintas para `MYSQL_PASSWORD` y `MYSQL_ROOT_PASSWORD` **antes del primer arranque**. Es un archivo local ignorado por Git. Luego ejecuta:

```bash
docker compose up -d --build --wait
```

Abre **http://localhost:8000**. Si el puerto está ocupado, cambia `APP_PORT` en `.env` y vuelve a ejecutar el comando. La primera construcción descarga PHP 8.3, Composer 2 y MySQL 8.4; puede tardar varios minutos.

Compose prepara el autoload, las extensiones PHP, la configuración por variables de entorno y las nueve semillas. Espera a que MySQL esté disponible antes de iniciar la web. La aplicación utiliza un usuario de BD propio; MySQL no publica ningún puerto en la PC. La web solo se publica en localhost. Este entorno utiliza el servidor integrado PHP para desarrollo y la demostración académica.

| Comando | Uso |
| --- | --- |
| `docker compose ps` | Comprobar que ambos servicios estén saludables. |
| `docker compose logs --tail=50 web db` | Revisar errores de arranque. |
| `docker compose down` | Detener sin borrar los datos. |
| `docker compose up -d --build --wait` | Arrancar o reconstruir tras descargar cambios. |
| `docker compose exec web php main.php` | Ejecutar la demostración de consola. |
| `docker compose exec web php app.php` | Abrir la consola interactiva. |

La BD, las imágenes y los tickets de consola se conservan en volúmenes Docker. Las semillas se importan **solo cuando la BD está vacía**, no en cada reinicio. No uses `docker compose down -v` si quieres conservar los datos: elimina esos volúmenes. Cambiar las contraseñas de `.env` después de inicializar MySQL no cambia automáticamente los usuarios que ya existen.

Esta opción no usa tu `config/config.php` local: construye la configuración dentro de la imagen a partir de la plantilla y recibe la contraseña del entorno. El código se copia a la imagen al construir; tras modificarlo hay que reconstruir con `--build`.

## Requisitos para ejecutar sin Docker

| Herramienta     | Versión mínima |
|-----------------|----------------|
| PHP             | 8.1 o superior |
| MySQL / MariaDB | 8.0.16 / 10.5  |
| Composer        | 2.x            |

PHP necesita las extensiones `pdo_mysql`, `mbstring` y `fileinfo`; Composer comprueba que estén disponibles. Verifica tus versiones:

```bash
php -v
mysql --version
composer --version
```

---

## Instalación — Aplicación Web sin Docker

### 1. Clonar el repositorio

```bash
git clone https://github.com/KendelJhoel/casob-puntoventacatalogo.git
cd casob-puntoventacatalogo
```

### 2. Instalar dependencias PHP

```bash
composer install
```

### 3. Configurar la conexión a la base de datos

```bash
cp config/config.example.php config/config.php
```

Abre `config/config.php` y ajusta el host, puerto, nombre de base de datos, usuario y contraseña según tu entorno local.

### 4. Crear la base de datos e importar el esquema

Los siguientes comandos usan redirección de Bash (Git Bash en Windows) o CMD:

```bash
# Crea la base (si no existe)
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS casob_puntoventa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importa el esquema (crea las tablas desde cero)
mysql -u root -p casob_puntoventa < database/schema.sql

# Importa los datos de ejemplo (9 ítems: 3 físicos, 3 digitales, 3 servicios)
mysql -u root -p casob_puntoventa < database/seed.sql
```

En PowerShell usa el comando `source` del cliente MySQL, desde la raíz del proyecto:

```powershell
mysql -u root -p -e "source database/schema.sql"
mysql -u root -p -e "source database/seed.sql"
```

`schema.sql` recrea las tablas y elimina sus datos anteriores. Úsalo para una base nueva o descartable, no para actualizar datos que debas conservar.

### 5. Levantar el servidor de desarrollo

```bash
php -S localhost:8000 -t public
```

### 6. Navegar la aplicación

Abre tu navegador en `http://localhost:8000` y explora los módulos:

| Ruta              | Módulo                    |
|-------------------|---------------------------|
| `index.php`       | Inicio / resumen          |
| `catalogo.php`    | Listado del catálogo      |
| `crear.php`       | Alta de ítem              |
| `editar.php?id=N` | Edición de ítem           |
| `detalle.php?id=N`| Ficha completa del ítem   |
| `eliminar.php?id=N`| Confirmación de borrado  |
| `venta_nueva.php` | Registrar una venta       |
| `ventas.php`      | Historial de ventas       |
| `venta.php?id=N`  | Ticket de una venta       |
| `reporte.php`     | Reporte del día           |

---

## Instalación — Versión Consola (Fase 1)

Solo requiere PHP y Composer (sin MySQL):

```bash
composer install
php app.php
```

### Navegación del menú

| Tecla | Acción                           |
|-------|----------------------------------|
| `A`   | Agregar un ítem al carrito       |
| `C`   | Cobrar y emitir ticket de compra |
| `L`   | Limpiar el carrito               |
| `S`   | Salir del sistema                |

El ticket se guarda en `tickets/ticket_XXXXXXXX.json`.

---

## Capturas de pantalla

| Módulo | Vista |
|--------|-------|
| Inicio | ![Inicio](public/assets/capturas/01-inicio.png) |
| Catálogo | ![Catálogo](public/assets/capturas/02-catalogo.png) |
| Alta de ítem | ![Crear](public/assets/capturas/03-crear.png) |
| Edición de ítem | ![Editar](public/assets/capturas/04-editar.png) |
| Ficha de ítem | ![Detalle](public/assets/capturas/05-detalle.png) |
| Nueva venta | ![Venta](public/assets/capturas/06-venta-nueva.png) |
| Historial de ventas | ![Ventas](public/assets/capturas/07-ventas.png) |
| Ticket de venta | ![Ticket](public/assets/capturas/08-ticket.png) |
| Reporte del día | ![Reporte](public/assets/capturas/09-reporte.png) |
| Confirmación de eliminación | ![Confirmación](public/assets/capturas/10-confirmacion.png) |
| Validación PHP con HTML5 desactivado | ![Errores del servidor](public/assets/capturas/11-validacion-servidor.png) |

---

## Estructura del proyecto

```
casob-puntoventacatalogo/
├── app.php                          # Punto de entrada consola
├── main.php                         # Catálogo de ejemplo para consola
├── composer.json
├── config/
│   ├── config.example.php           # Plantilla de configuración (versionar)
│   └── config.php                   # Configuración local (ignorada por Git)
├── database/
│   ├── schema.sql                   # Crea tablas desde cero
│   └── seed.sql                     # Datos de ejemplo (3 por tipo)
├── public/                          # Raíz web (apuntar aquí el servidor)
│   ├── index.php                    # Inicio
│   ├── catalogo.php
│   ├── crear.php / editar.php / detalle.php / eliminar.php
│   ├── venta_nueva.php / ventas.php / venta.php / reporte.php
│   ├── _init.php                    # Bootstrap: PDO, sesión, helpers
│   ├── css/estilos.css              # Hoja de estilos única y propia
│   ├── assets/
│   │   ├── forms.js                 # Lógica JS del formulario
│   │   └── sin-imagen.svg           # Imagen por defecto
│   └── uploads/                     # Imágenes subidas (ignoradas por Git)
├── src/
│   ├── Contratos/Facturable.php
│   ├── Excepciones/StockInsuficienteException.php
│   ├── Infraestructura/             # Conexión PDO
│   ├── Modelos/                     # ItemVendible, ProductoFisico, etc.
│   ├── Repositorios/                # RepositorioItems, RepositorioVentas
│   └── Servicios/                   # Carrito, GestorImagenes, Validador
├── views/
│   ├── layout/encabezado.php, pie.php
│   └── partials/formulario_item.php
├── compose.yml / Dockerfile         # Entorno local reproducible
├── .env.example                    # Variables de ejemplo para Compose
├── tests/                           # Scripts de comprobación
└── tickets/                         # Tickets de consola (.json, ignorados por Git)
```

---

## Autoloading

El proyecto usa **PSR-4** vía Composer. El namespace raíz `App\` mapea a `src/`:

```json
"autoload": {
    "psr-4": {
        "App\\": "src/"
    }
}
```

Si agregas nuevas clases, regenera el autoloader:

```bash
composer dump-autoload
```

## Comprobaciones

En una instalación local con una **base descartable** y las nueve semillas:

```bash
php tests/check_miguel.php
php tests/check_images.php
php tests/check_seguridad.php
CASOB_TEST_DB=1 php tests/check_ventas.php
CASOB_TEST_DB=1 CASOB_TEST_URL=http://127.0.0.1:8000 php tests/check_web.php
```

En PowerShell establece primero `$env:CASOB_TEST_DB='1'` y `$env:CASOB_TEST_URL='http://127.0.0.1:8000'`, y ejecuta los mismos archivos PHP. La prueba web necesita el servidor encendido.

Con Compose, sobre datos de prueba:

```bash
docker compose exec web php tests/check_miguel.php
docker compose exec web php tests/check_images.php
docker compose exec web php tests/check_seguridad.php
docker compose exec -e CASOB_TEST_DB=1 web php tests/check_ventas.php
docker compose exec -e CASOB_TEST_DB=1 web php tests/check_web.php
```

Las pruebas de BD crean/modifican registros temporales y luego los limpian. No ejecutarlas contra datos reales ni mientras alguien modifica el catálogo. La revisión estática de seguridad es una ayuda; se complementa con las pruebas HTTP de CSRF, escape, validación, stock y transacciones.

Verificación técnica del 01/10/2026: las diez páginas renderizadas pasaron el validador W3C sin errores ni avisos; respuestas guardadas en [tests/evidencias/html5.json](tests/evidencias/html5.json). También se probaron escritorio/móvil, 47 comprobaciones HTTP adicionales y persistencia de la base e imágenes al recrear los contenedores. Esta evidencia corresponde al código corregido y debe repetirse si se modifica el HTML.

Referencia del arranque con dependencias saludables: [documentación de Docker Compose](https://docs.docker.com/compose/how-tos/startup-order/).

---

## Integrantes

| Nombre        | Rama      | Responsabilidad principal                     |
|---------------|-----------|-----------------------------------------------|
| Miguel        | `Miguel`  | Fase 1 — Modelo POO, BD y repositorio         |
| Diego         | `Diego`   | Fase 1 — Interfaz web, imágenes               |
| Kendel Jhoel  | `ken`     | Fase 2 — Ventas, ticket y reporte             |
| Rodolfo Rivas | `Rodolfo` | Fase 3 — Verificación, README e informe final |
