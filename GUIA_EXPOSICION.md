# Guía de exposición y pruebas — Caso B, Fase 2

Esta guía indica qué demostrar y qué evidencias guardar. El informe académico lo redacta el equipo. La referencia es `Proyecto_Fase2_Aplicacion_Web_PHP.pdf`, secciones 3, 5, 6, 7, 9, 10 y 11; la calificación también depende del informe y de la defensa individual.

## 1. Preparación antes de la exposición

1. Descargar `main` actualizado: `git switch main` y `git pull --ff-only origin main` (con el directorio de trabajo limpio).
2. Tener Docker Desktop abierto. Copiar `.env.example` a `.env` **solo si no existe** y configurar las dos contraseñas. No mostrar ese archivo en la exposición.
3. Ejecutar `docker compose up -d --build --wait`. Hacer la primera descarga/construcción antes de llegar a clase: necesita Internet y tarda varios minutos.
4. Ejecutar `docker compose ps`: `web` y `db` deben estar saludables. Abrir `http://localhost:8000` o el puerto de `APP_PORT` en `.env`. En la PC de Kendel se probó el **8012**.
5. Preparar una imagen JPG/PNG/WEBP menor de 2 MB, una mayor de 2 MB y un archivo de texto renombrado a `.jpg`. El último sirve para demostrar que se revisa el contenido real.
6. Usar una BD de demostración, sin datos importantes. Las nueve semillas son tres registros por tipo. No ejecutar `schema.sql` ni `docker compose down -v` sobre datos que se deban conservar.
7. Abrir de antemano el navegador, el editor con `src/`, GitHub y las capturas. Llevar la rúbrica impresa, como pide el PDF.

Las pruebas automáticas necesitan las semillas originales; ejecutarlas antes de modificar productos durante el ensayo. Para repetir esta demostración, usar SKU nuevos o borrar únicamente los productos de ensayo que uno mismo creó.

## 2. Recorrido principal de la demostración

Tiempo orientativo: 10–15 minutos, ajustándolo al tiempo que dé la docente. No limitarse a enseñar pantallas: hacer operaciones reales y explicar el resultado.

| Paso | Qué hacer | Qué debe verse / qué demuestra | Captura sugerida |
| --- | --- | --- | --- |
| 1 | Abrir Inicio. | Nombre del sistema, Caso B, navegación y total de ítems leído de la BD. | `01-inicio` |
| 2 | Abrir Catálogo. | Tres clases de ítem, miniaturas, precios finales y acciones Ver/Editar/Eliminar. Explicar que el precio final depende del objeto. | `02-catalogo` |
| 3 | Abrir Nuevo ítem y cambiar entre físico, digital y servicio. | El formulario adapta su campo propio: existencias, enlace o fecha. Cada campo tiene etiqueta e imagen obligatoria. | `03-formulario` |
| 4 | Crear los tres ejemplos de la tabla siguiente, con imagen válida. | Aparecen en el catálogo; mensaje de éxito; redirección a la ficha. Recargar la ficha no duplica el registro (PRG). | `04-alta-correcta` |
| 5 | Abrir la ficha de cada ejemplo. | Datos persistidos, imagen y detalle específico obtenido del modelo. Comparar sus precios finales. | `05-detalle` |
| 6 | Editar el físico y reemplazar su imagen, manteniendo precio 10 y stock 3 para la venta. | Formulario precargado, imagen nueva; el archivo anterior se elimina. | `06-edicion` |
| 7 | Abrir Cobrar venta. Cliente `Cliente de exposición`; vender 2 físicos, 1 digital y 1 servicio de los ejemplos. | Se guarda una venta con tres líneas. Total esperado **$76.00**. | `07-venta` |
| 8 | Abrir el ticket, recargar y volver al catálogo. | Ticket con fecha, cliente, cantidades y total. El físico queda con **stock 1**; recargar no vuelve a cobrar ni descuenta otra vez. | `08-ticket` |
| 9 | Consultar Ventas y Reporte. | La venta aparece en historial y reporte del día. Si ya había ventas, el total diario suma esas ventas más $76.00. | `09-historial`, `10-reporte` |
| 10 | Intentar vender 2 unidades del mismo físico, que ahora tiene stock 1. Incluir también 1 digital. | Se rechaza toda la venta: no hay ticket nuevo ni descuento parcial de stock. Demuestra transacción y rollback. | `11-stock-insuficiente` |
| 11 | Cambiar nombre y precio del físico a `Producto editado`, precio 15. Consultar el ticket anterior. | El ticket conserva el nombre original y el precio unitario histórico de **$11.50**. | `12-ticket-historico` |
| 12 | Desde Catálogo, pulsar Eliminar sobre ese físico. Primero Cancelar, luego confirmar. | GET solo muestra la confirmación. Cancelar conserva el ítem; confirmar por POST elimina registro e imagen. El ticket anterior permanece. | `13-confirmacion`, `14-borrado` |
| 13 | Reducir la ventana a unos 390 px o usar el modo móvil de las herramientas del navegador. | Catálogo, formulario, ticket y reporte se adaptan sin desbordar horizontalmente la página. | `15-movil` |

### Datos concretos para no improvisar los cálculos

| Tipo | SKU | Nombre | Precio base | Campo propio | Precio final |
| --- | --- | --- | ---: | --- | ---: |
| Físico | `EXPO-FIS-01` | Mouse de exposición | 10.00 | Stock 3 | 11.50: base + 15 % |
| Digital | `EXPO-DIG-01` | Manual de exposición | 20.00 | `https://example.com/manual.pdf` | 18.00: base − 10 % |
| Servicio | `EXPO-SRV-01` | Asesoría de exposición | 30.00 | Una fecha y hora válidas, por ejemplo una fecha próxima | 35.00: base + 5 |

Venta del paso 7: `2 × 11.50 + 1 × 18.00 + 1 × 35.00 = 76.00`. Usar los ejemplos recién creados, no otros productos del catálogo. La validación actual exige una fecha real; no exige que sea futura.

## 3. Validaciones que conviene mostrar expresamente

La docente evalúa tanto HTML5 como PHP. Mostrar solamente el aviso del navegador no prueba la validación del servidor.

| Prueba | Cómo hacerla | Resultado esperado |
| --- | --- | --- |
| Campos obligatorios en cliente | Intentar guardar el formulario vacío. | El navegador impide enviar. |
| Formato del SKU | Introducir `SKU CON ESPACIOS!`. | El navegador rechaza el patrón; PHP también lo rechaza si se omite HTML5. |
| **Validación PHP sin HTML5** | Abrir herramientas del navegador, pestaña Elementos; seleccionar el `<form>` y añadir `novalidate`. Escribir un SKU válido, precio `-1`, stock `-1` y dejar nombre vacío. Enviar. | Respuesta HTTP **422**, errores junto a los campos y conservación del SKU y valores introducidos. Guardar captura del formulario y, si es posible, de Red/Network mostrando el POST. |
| SKU repetido | Intentar crear otro ítem con un SKU existente y una imagen válida. | Mensaje `Este SKU ya existe.`; no se crea otro registro ni queda una imagen huérfana. |
| URL incorrecta | Elegir digital e introducir una URL que no sea HTTP/HTTPS. Desactivar HTML5 si bloquea el envío. | Error junto al enlace de descarga. |
| Imagen falsa | Subir el archivo de texto renombrado a `.jpg`. | PHP lo rechaza por no ser una imagen real, aunque la extensión parezca correcta. |
| Imagen demasiado grande | Subir una imagen de más de 2 MB. | Error de tamaño; no se guarda el ítem. |
| CSRF | En un formulario de alta, editar el campo oculto `_csrf` en Elementos y poner `incorrecto`; desactivar HTML5 y enviar. | HTTP **403**; no se crea un registro. Recargar el formulario después para recuperar un token válido. |
| Escape HTML | Crear un ejemplo con nombre `Producto <b>prueba</b>`. | Se ven los caracteres `<b>` como texto, no como formato HTML. Borrar el ejemplo al terminar. |
| Stock insuficiente | Seguir el paso 10 del recorrido. | Error visible, venta completa cancelada y stock sin cambios. |

Recargar la página restaura el formulario original después de alterar HTML en las herramientas del navegador. Las capturas deben mostrar pruebas realmente ejecutadas, no resultados escritos a mano sobre una imagen.

## 4. Qué código abrir si lo preguntan

| Concepto que pide el PDF | Archivo o método real | Explicación que deben poder dar |
| --- | --- | --- |
| Abstracción e interfaz | `src/Modelos/ItemVendible.php`, `src/Contratos/Facturable.php` | El contrato define operaciones comunes; la clase abstracta no se instancia directamente. |
| Encapsulamiento | Constructor de `ItemVendible`; `ProductoFisico::reducirStock()` | Los atributos están protegidos y las invariantes se validan. El stock se modifica mediante operaciones controladas. |
| Herencia y polimorfismo | Las tres subclases y `calcularPrecioFinal()/obtenerDetalle()` | La misma llamada produce el comportamiento de cada tipo, sin preguntar por la clase en el listado. Mostrar los tres cálculos del ejemplo. |
| Fábrica | `src/Modelos/ItemFactory.php` | Convierte la fila/formulario en la subclase correcta; concentra la selección del tipo. |
| Validación específica | `Validador::item()` y `validarCamposPropios()` de cada subclase | El validador reúne errores comunes y delega los propios de cada tipo mediante la fábrica. |
| Composición | `src/Servicios/Carrito.php` | El carrito reúne ítems facturables para calcular y emitir la compra; explicar su relación real, no solo leer la etiqueta. |
| PDO e inyección de dependencias | `Conexion::crear()` y constructores de los repositorios | Se recibe PDO por constructor. SQL queda en repositorios, fuera de las páginas. |
| CRUD y SQL preparado | `RepositorioItems::crear()/listar()/buscar()/actualizar()/eliminar()` | SQL y valores del usuario se pasan por separado; explicar `prepare()` y `execute()`. |
| Venta atómica | `RepositorioVentas::registrar()` | Transacción, bloqueo de filas, descuento de stock, cabecera/detalles, commit o rollback. |
| Ticket histórico | `venta_detalles`, `RepositorioVentas::buscar()` | Se copian nombre, SKU, precio y detalle al vender; `item_id` puede quedar nulo al borrar sin perder la venta. |
| Imágenes | `GestorImagenes::validar()/guardar()/eliminar()` | MIME real, tamaño, nombre aleatorio, `move_uploaded_file()` y limpieza de archivos. |
| CSRF, escape y PRG | `public/_init.php` | Token de sesión con comparación segura; `htmlspecialchars()`; redirección HTTP 303 después de guardar. |
| Layout y CSS | `views/layout/`, `views/partials/`, `public/css/estilos.css` | Fragmentos compartidos, una hoja propia, variables, Grid/Flex y media queries. |
| Persistencia | `database/schema.sql`, `database/seed.sql`, `compose.yml` | Tabla única para la jerarquía, FK, restricciones y tres ejemplos por tipo; volúmenes conservan datos. |

También deben poder ejecutar `docker compose exec web php main.php` y explicar cómo se conservó la versión de consola de Fase 1.

## 5. Comprobaciones automáticas de respaldo

Ejecutar sobre datos de prueba, preferiblemente antes del ensayo manual:

```bash
docker compose exec web php tests/check_miguel.php
docker compose exec web php tests/check_images.php
docker compose exec web php tests/check_seguridad.php
docker compose exec -e CASOB_TEST_DB=1 web php tests/check_ventas.php
docker compose exec -e CASOB_TEST_DB=1 web php tests/check_web.php
```

Guardar una captura de sus resultados. Complementan la demostración manual; no sustituyen explicarla. Para HTML5, `tests/evidencias/html5.json` contiene las respuestas del validador de las diez páginas corregidas. Se puede validar de nuevo el código HTML renderizado mediante la opción de entrada directa del W3C; su servidor no puede abrir el `localhost` de tu PC.

Para probar persistencia con Compose: crear un producto con imagen, ejecutar `docker compose down`, luego `docker compose up -d --wait` y comprobar que ambos permanecen. No añadir `-v`. Hacerlo antes de clase si el tiempo de exposición es corto.

## 6. Material que el equipo debe reunir para el informe

El PDF de la consigna pide estos diez contenidos; esta lista es una guía de evidencias, no el informe redactado:

1. **Portada:** proyecto, Caso B, nombres completos, fecha y enlace al repositorio.
2. **Cambios respecto a la propuesta:** comparar con la propuesta real de Fase 1 y explicar las decisiones.
3. **Modelo relacional:** diagrama ER y justificación de tabla única para la herencia; representar `item_id` opcional tras borrar el producto.
4. **Clases actualizadas:** incluir repositorios, fábrica, validador y gestor de imágenes, usando los nombres reales del código actual.
5. **Correspondencia clases/tablas:** señalar columnas comunes y específicas, además de ventas y detalles.
6. **Validaciones:** tabla con campo, regla HTML5, regla PHP y mensaje exacto que apareció al probar.
7. **Evidencias:** capturas de todas las páginas, confirmación de borrado, formularios con errores y validación de servidor con `novalidate`.
8. **Cuatro pilares:** señalar su aplicación en esta versión web con archivos y ejemplos concretos.
9. **Distribución real del trabajo:** coherente con los aportes y commits; distinguir los commits heredados de una rama de los nuevos.
10. **Conclusiones y dificultades:** experiencias reales del equipo y cómo las resolvieron.

Además, preparar las seis preguntas de reflexión de la consigna: estrategia de herencia en BD; por qué la fábrica conoce los tipos y cómo agregar otro; ataque que evita SQL preparado con un ejemplo; por qué validar también en PHP; por qué no confiar en extensión/nombre de imagen; ventajas de repositorios sobre SQL en páginas.

## 7. Git y entrega que deben mostrar

### Cómo evaluará la docente (rúbrica, páginas 16–17)

| Criterio | Puntos | Qué demostrar para el nivel excelente |
| --- | ---: | --- |
| HTML y CSS | 15 | Ocho páginas mínimas completas, HTML válido y semántico, CSS propio, variables y diseño adaptable. |
| Base de datos y CRUD | 25 | Modelo relacional justificado, FK/restricciones, CRUD completo, alta/listado de ventas, PDO preparado en repositorios. |
| Formularios | 10 | Conservación de valores, errores por campo, PRG, flash, CSRF y eliminación por POST con confirmación. |
| Imágenes | 10 | MIME real y tamaño, nombre único, reemplazo y eliminación del archivo anterior, imagen por defecto. |
| Validación y seguridad | 15 | Validación HTML5 y PHP/modelo, salida escapada, credenciales fuera de Git y errores PDO ocultos al usuario. |
| Continuidad POO | 10 | Clases originales reutilizadas, fábrica única y polimorfismo genuino en listado, ficha y reporte. |
| Documentación e informe | 5 | Etiquetas correctas, informe completo y README que permite ejecutar el proyecto. |
| Git y GitHub | 10 | Al menos 20 commits nuevos, cinco por integrante, tres fechas reales, mensajes requeridos, etiqueta y `.gitignore`. |
| **Total** | **100** | La nota no depende solamente de que la web abra. |

El PDF también indica que si alguien no puede explicar el código atribuido a su nombre, su nota individual puede reducirse hasta un 30 % respecto de la del equipo. Preparar todos los módulos, no únicamente la parte que uno presentó.

- Mismo repositorio público de Fase 1 y confirmación de que la docente es colaboradora.
- Historial con mínimo 20 commits nuevos de la fase, mínimo cinco por integrante y al menos tres fechas reales. Las correcciones posteriores son aportes adicionales, no reemplazan los veinte iniciales.
- Rama `ken` y `main` con las correcciones revisadas; autoría de las correcciones: KendelJhoel.
- La etiqueta `v2.0` ya existe, pero corresponde al corte original `407d019`, anterior a estas correcciones. Para ejecutar el código corregido, descargar **`main` actualizado**. Coordinar la versión final que se entregará; no afirmar que el tag antiguo contiene cambios posteriores.
- README con instalación reproducible, SQL y capturas; informe elaborado por el equipo; rúbrica impresa y demostración lista.

Cada integrante debe poder explicar cualquier módulo: la consigna contempla preguntas individuales, incluso sobre código fuera de su parte habitual.
