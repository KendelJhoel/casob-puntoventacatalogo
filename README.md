# casob-puntoventacatalogo

Sistema de **Punto de Venta por consola** desarrollado en PHP con Programación Orientada a Objetos. Permite gestionar un catálogo de productos y servicios, armar un carrito de compras y generar un ticket de venta.

> **Caso B** — Ejercicio académico de POO en PHP: herencia, interfaces, excepciones personalizadas y autoloading PSR-4.

---

## Características

- 🛒 Carrito de compras interactivo por terminal
- 📦 **Productos Físicos** — con control de stock
- 💻 **Productos Digitales** — sin stock, con enlace de descarga
- 🛠️ **Servicios** — agendables, sin stock
- 🧾 Emisión de ticket de compra (guardado en `/tickets`)
- ⚠️ Excepción personalizada para stock insuficiente

---

## Requisitos

| Herramienta | Versión mínima |
|-------------|----------------|
| PHP         | 8.1 o superior |
| Composer    | 2.x            |

Verificá tu versión de PHP:

```bash
php -v
```

---

## Instalación

```bash
# 1. Clonar el repositorio
git clone https://github.com/KendelJhoel/casob-puntoventacatalogo.git
cd casob-puntoventacatalogo

# 2. Instalar dependencias (genera el autoloader de Composer)
composer install
```

---

## Uso

Ejecutá el punto de entrada principal desde la raíz del proyecto:

```bash
php app.php
```

### Navegación del menú

Una vez iniciado el sistema, verás el catálogo de productos y las siguientes opciones:

| Tecla | Acción                          |
|-------|---------------------------------|
| `A`   | Agregar un ítem al carrito      |
| `C`   | Cobrar y emitir ticket de compra|
| `L`   | Limpiar el carrito              |
| `S`   | Salir del sistema               |

### Flujo típico

```
1. Presioná [A] para agregar un producto.
2. Ingresá el número del ítem (ej: 1 para Auriculares Bluetooth).
3. Indicá la cantidad deseada.
4. Repetí los pasos para agregar más ítems.
5. Presioná [C] para cobrar → el ticket se guarda en /tickets.
6. Presioná [S] para salir.
```

### Ejemplo de sesión

```
  Selecciona una opción: A
  Ingresa el número del producto: 1
  Cantidad: 2

  ✔ Auriculares Bluetooth x2 agregado al carrito.

  Selecciona una opción: C

  ══════════════ TICKET DE COMPRA ══════════════
  Auriculares Bluetooth x2       $115.00
  ─────────────────────────────────────────────
  TOTAL:                         $115.00
  ══════════════════════════════════════════════
  Ticket guardado en: tickets/ticket_XXXXXXXX.txt
```

---

## Estructura del proyecto

```
casob-puntoventacatalogo/
├── app.php                          # Punto de entrada (UI interactiva)
├── composer.json
├── src/
│   ├── Contratos/
│   │   └── Facturable.php           # Interface base para ítems vendibles
│   ├── Excepciones/
│   │   └── StockInsuficienteException.php
│   ├── Modelos/
│   │   ├── ItemVendible.php         # Clase abstracta base
│   │   ├── ProductoFisico.php       # Hereda de ItemVendible, maneja stock
│   │   ├── ProductoDigital.php
│   │   └── Servicio.php
│   └── Servicios/
│       └── Carrito.php              # Lógica del carrito y emisión de ticket
└── tickets/                         # Tickets generados (ignorados por git)
```

---

## Autoloading

El proyecto usa el estándar **PSR-4** via Composer. El namespace raíz `App\` mapea a `src/`:

```json
"autoload": {
    "psr-4": {
        "App\\": "src/"
    }
}
```

Si agregás nuevas clases, regenerá el autoloader con:

```bash
composer dump-autoload
```

---

## Autor

**Rodolfo Rivas**
