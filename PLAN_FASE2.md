# Plan y bitácora de la Fase 2 — Caso B: punto de venta

**Fuente:** `C:\Users\user\Desktop\Proyecto_Fase2_Aplicacion_Web_PHP.pdf` (18 páginas). **Línea base:** al iniciar, `main`, `ken`, `Miguel`, `Diego` y `Rodolfo` apuntaban a `46021cf`. **Estado actual:** Miguel completó su parte de persistencia y modelo; Diego no ha comenzado. Este documento reúne el plan y el registro de ejecución; **Pendiente** no significa realizado.

| Fase de trabajo | Fecha local (Guatemala) | Rama y responsable previsto | Commits de trabajo | Resultado que debe quedar funcionando |
| --- | --- | --- | ---: | --- |
| **1 — Catálogo web y persistencia** | Martes 29/09/2026 | `Miguel` y `Diego` | **10: cinco y cinco** | Base recreable, modelo POO reutilizado, CRUD completo del catálogo, imágenes, validación, CSRF e interfaz adaptable. Es la fase más grande. |
| **2 — Ventas y reporte** | Miércoles 30/09/2026 | `ken` (Kendel) | **5** | Ventas y detalle en transacción, stock correcto, ticket y reporte web desde MySQL, pruebas de los flujos completos. También es compleja. |
| **3 — Verificación y entrega** | Jueves 01/10/2026 | `Rodolfo` | **5** | Instalación comprobada, capturas, README e informe PDF completos, revisión final y etiqueta `v2.0`. Menos desarrollo nuevo; se concentra en demostrar y cerrar lo ya construido. |

**Meta:** 20 commits de trabajo nuevos como mínimo, cinco por integrante en tres fechas reales. Si una prueba revela un defecto, se añade el commit de corrección necesario: **20 no es un límite**. Los posibles commits de integración en `main` son adicionales. Cada cambio se hace y se sube a la rama indicada; al cerrar la fase 1 se integra y comprueba en `main` para que el trabajo esté disponible en la siguiente. Registrar en esta bitácora el autor real, el hash, la prueba y el push. La cuenta usada para subir, la rama y el autor del commit son datos distintos; la rúbrica exige que el aporte atribuido a cada integrante sea real y defendible.

Las fechas de la tabla son la programación solicitada. Si un trabajo termina después de medianoche, anotar su **fecha real** y ajustar la planificación; nunca modificar la fecha de Git para que encaje en el cuadro.

Este archivo se crea **sin commit** mientras solo es un plan. Versionarlo junto con el primer cambio real y actualizarlo en los commits siguientes; no gastar un commit aislado en el plan. Antes de redactar la portada del informe, localizar la propuesta escrita de Fase 1 y confirmar los nombres completos de los cuatro integrantes: no están todos en el repositorio actual y no deben inventarse.

**Mensajes de Git:** el PDF exige Conventional Commits e identificar el módulo. Para respetar el estilo del grupo, los mensajes usan el tipo (`feat:`, `docs:`, `test:`) y mencionan el módulo en la frase, sin `feat(modulo)` ni títulos de plantilla. Cada mensaje describe un cambio concreto. No crear commits vacíos para completar el número.

## Fase 1 — Martes: Miguel y Diego, 10 commits

**Orden de trabajo:** Miguel deja estable el modelo, la base y las reglas; Diego construye los flujos web sobre eso. Compartir e integrar el avance de Miguel antes de que Diego conecte las páginas. Cada commit debe pasar `php -l`; los que tocan SQL/PDO se prueban contra MySQL/MariaDB real.

| # | Rama | Mensaje previsto | Cambio y criterio para darlo por hecho | Estado / hash / prueba |
| ---: | --- | --- | --- | --- |
| 1 | `Miguel` | `feat: Dejé listas las tablas del catálogo y las ventas` | `database/schema.sql`: ítems de tres tipos en tabla única, ventas y detalle; PK AUTO_INCREMENT, FK, `DECIMAL`, fecha, imagen, `NOT NULL`, `UNIQUE` y `CHECK`. El detalle conserva nombre y precio históricos; la FK del ítem permite borrar del catálogo sin destruir tickets (`ON DELETE SET NULL` y datos copiados en el detalle). El script recrea la base desde cero. | **Hecho** · hash: d763698 · prueba: esquema en MySQL 8 |
| 2 | `Miguel` | `feat: La base ya arranca con ejemplos de cada tipo` | `database/seed.sql` con mínimo tres físicos, tres digitales y tres servicios; conexión PDO inyectable, `config/config.example.php`, `.gitignore` para configuración real, `vendor/` y subidas. Importar los dos SQL y consultar los nueve ítems sin problemas de UTF-8. | **Hecho** · hash: 9424127 · prueba: nueve semillas y PDO |
| 3 | `Miguel` | `feat: El catálogo recupera las clases originales al leer la base` | Reutilizar `ItemVendible`, `ProductoFisico`, `ProductoDigital` y `Servicio`; adaptar identidad/imagen sin romper `main.php`. Fábrica única para convertir fila y formulario en subclase; el formulario obtiene metadatos de campos propios sin decidir el tipo en la vista. Precios y detalles siguen siendo polimórficos. Revisar los `instanceof` heredados de `app.php` y `Carrito.php` para que listados/cálculos cumplan la regla sin romper la consola. Marcar `[ABSTRACCION]`, `[HERENCIA]`, `[POLIMORFISMO]`, `[INTERFAZ]` y `[FABRICA]` donde correspondan. | **Hecho** · hash: 06009aa · prueba: `php main.php` y sintaxis |
| 4 | `Miguel` | `feat: Ya podemos guardar y consultar productos en MySQL` | Repositorio con PDO por constructor y consultas preparadas para crear, listar, buscar, actualizar y eliminar ítems. Ninguna página escribe SQL. Comprobar las cuatro operaciones con datos reales y que borrar un ítem no destruya tickets históricos. Marcar `[CRUD-CREATE]`, `[CRUD-READ]`, `[CRUD-UPDATE]`, `[CRUD-DELETE]`, `[SEGURIDAD]` y `[INYECCION-DEPENDENCIAS]`. | **Hecho** · hash: f00fcba · prueba: CRUD e historial en MySQL |
| 5 | `Miguel` | `feat: La validación del catálogo rechaza productos incorrectos` | Clase `Validador` con todos los errores por campo; reglas de precio > 0, stock físico entero ≥ 0, URL digital válida, nombre/SKU y fecha de servicio válidos. Reforzar invariantes en las clases y SQL; prueba pequeña que rechace datos incorrectos aun sin HTML5. Marcar `[VALIDACION]` y `[ENCAPSULAMIENTO]`. | **Hecho** · hash: último commit de `Miguel` · prueba: `php tests/check_miguel.php` |
| 6 | `Diego` | `feat: Armé el inicio y el diseño de la web` | `public/` como raíz, layout compartido con `header/nav/main/footer`, inicio con resumen real y accesos, una sola hoja CSS propia con variables, Grid/Flexbox y media query ≤ 768 px. Arranque común, escape `htmlspecialchars()`, sesión, token CSRF, flash y redirección PRG reutilizables. Marcar `[SEGURIDAD]` y `[PRG]`. | **Pendiente** · hash: — · prueba: — |
| 7 | `Diego` | `feat: El catálogo ya se puede recorrer desde el navegador` | Listado con miniatura, dato calculado polimórficamente, acciones y estado vacío; ficha completa con imagen y detalle del objeto. HTML5 semántico, `label` y `alt` donde corresponda, salida escapada, sin `instanceof` ni condicionales por tipo en vistas. Probar escritorio y móvil. | **Pendiente** · hash: — · prueba: — |
| 8 | `Diego` | `feat: Las imágenes ya se guardan sin confiar en su nombre` | `GestorImagenes`: error de subida, máximo 2 MB, MIME real JPG/PNG/WEBP con `finfo`, nombre aleatorio, `move_uploaded_file()`, imagen por defecto, reemplazo y eliminación. Evitar archivos huérfanos si falla la base. Probar archivo válido, MIME falso y tamaño excesivo. Marcar `[VALIDACION]` y `[SEGURIDAD]`. | **Pendiente** · hash: — · prueba: — |
| 9 | `Diego` | `feat: Ya se pueden registrar productos del catálogo desde la web` | Formulario para los tres tipos, imagen obligatoria al registrar, atributos HTML5 y validación PHP, errores al lado del campo y valores conservados. Guardar por POST preparado, verificar CSRF, mostrar flash y aplicar PRG. Crear un ítem de cada tipo en MySQL. | **Pendiente** · hash: — · prueba: — |
| 10 | `Diego` | `feat: El catálogo ya permite editar y eliminar productos` | Formulario precargado con imagen actual y reemplazo; confirmación de eliminación por POST, nunca por enlace GET. Validación, CSRF, flash, PRG, limpieza de imágenes y preservación del historial de ventas. Probar editar, reemplazar, borrar y recargar sin duplicar. | **Pendiente** · hash: — · prueba: — |

**Cierre obligatorio de fase 1:** ejecutar instalación limpia; comprobar inicio, listado, crear, editar, ficha y confirmación; usar `novalidate` para probar PHP; verificar imágenes y SQL. Registrar en la bitácora por qué se eligió tabla única, la relación clase↔tabla y la tabla de validaciones: Rodolfo necesitará esos datos para el informe. Si cualquiera falla, la fase sigue **En curso**, aunque ya existan diez commits.

## Fase 2 — Miércoles: Kendel, 5 commits

**Entrada:** catálogo completo e integrado. **Salida:** venta, ticket y reporte funcionando desde la base. Cada venta confirma líneas y stock en una sola transacción; los datos de un ticket no cambian si después se modifica el catálogo.

| # | Rama | Mensaje previsto | Cambio y criterio para darlo por hecho | Estado / hash / prueba |
| ---: | --- | --- | --- | --- |
| 11 | `ken` | `feat: El cobro ya guarda la venta y descuenta existencias` | Repositorio de ventas/detalle con PDO preparado y transacción. Validar cantidades positivas, bloquear o actualizar condicionalmente el stock físico, impedir sobreventa y guardar copia del nombre/SKU/precio unitario final en cada línea. Probar rollback y venta de los tres tipos. | **Pendiente** · hash: — · prueba: — |
| 12 | `ken` | `feat: Ya podemos cobrar ventas desde el navegador` | Formulario POST de venta con cliente, ítems y cantidades; errores visibles, valores conservados, CSRF, flash y PRG. No aceptar venta vacía, cantidad cero ni más stock del disponible. La página no contiene SQL. | **Pendiente** · hash: — · prueba: — |
| 13 | `ken` | `feat: Ahora se pueden consultar los tickets guardados` | Listado y detalle de ventas con su ticket HTML, fecha, líneas, cantidades y total desde MySQL. Escapar todo dato y usar valores históricos; comprobar que editar/borrar un producto no cambie un ticket emitido. | **Pendiente** · hash: — · prueba: — |
| 14 | `ken` | `feat: El reporte del día ya sale de ventas reales` | Consultas de reporte en repositorio y página HTML. Repetir el reporte/ticket polimórfico de Fase 1 con métodos de los objetos, sin `instanceof`, `get_class` o decisiones por tipo fuera de la fábrica. Probar con ventas de cada clase. | **Pendiente** · hash: — · prueba: — |
| 15 | `ken` | `test: Dejé comprobados los flujos de compra en la web` | Una comprobación ejecutable de catálogo, venta, stock insuficiente, persistencia del ticket, CSRF y validación de servidor; ejecutar además `php -l` y navegación real por las ocho páginas. Corregir cualquier fallo antes de cerrar la fase, con commits extra si el arreglo merece uno propio. | **Pendiente** · hash: — · prueba: — |

**Cierre obligatorio de fase 2:** las ocho páginas funcionan; CRUD principal y alta/listado de ventas están completos; la venta con stock insuficiente no modifica la DB; el reporte y los tickets leen datos persistidos. Guardar resultados de pruebas y capturas provisionales de venta/reporte para el informe. La fase 3 se dedica a verificar y entregar, no a terminar funciones básicas.

**Ocho páginas que se comprobarán:** inicio, catálogo, alta de ítem, edición de ítem, ficha de ítem, nueva venta, listado/detalle de ventas y reporte. La confirmación de borrado y el ticket pueden ser vistas adicionales o formar parte de las anteriores; deben funcionar igualmente.

## Fase 3 — Jueves: Rodolfo, 5 commits

El código funcional debe existir al empezar este día. Esta fase tiene menos desarrollo nuevo: instalación reproducible, seguridad, evidencias e informe. Si la revisión descubre un error funcional, se corrige y se vuelve a probar antes de `v2.0`.

| # | Rama | Mensaje previsto | Cambio y criterio para darlo por hecho | Estado / hash / prueba |
| ---: | --- | --- | --- | --- |
| 16 | `Rodolfo` | `docs: Dejé claros en README los pasos para arrancar la web` | README con caso B, cuatro integrantes, requisitos PHP/MySQL/Composer, clonar, `composer install`, copiar configuración, importar `schema.sql`/`seed.sql`, iniciar `php -S localhost:8000 -t public` y navegar módulos. Seguir esos pasos en una instalación limpia y corregirlos si fallan. | **Pendiente** · hash: — · prueba: — |
| 17 | `Rodolfo` | `test: Comprobé la seguridad de formularios e imágenes` | Comprobación ejecutable o registro reproducible de CSRF, POST sin HTML5, salida escapada, consulta preparada, MIME/tamaño indebido y eliminación solo por POST. Revisar también que no se versionen `config.php`, `vendor/` ni imágenes subidas; comprobar que las etiquetas `[CONCEPTO]`, incluida `[COMPOSICION]`, estén en el punto exacto donde se aplican. | **Pendiente** · hash: — · prueba: — |
| 18 | `Rodolfo` | `docs: Guardé las pantallas de la aplicación terminada` | Capturas reales de las ocho páginas, formularios con errores y prueba de validación del servidor; añadir al README las principales, con rutas que funcionen en GitHub. Comprobar HTML5 con el validador W3C y revisar diseño móvil. | **Pendiente** · hash: — · prueba: — |
| 19 | `Rodolfo` | `docs: Expliqué el modelo y los cambios desde la consola` | Fuente editable del informe que continúa la propuesta de Fase 1: portada, cambios, diagrama ER, justificación de tabla única, clases actualizadas, correspondencia clases/tablas, reglas cliente/servidor y cuatro pilares. Usar diagramas y tablas que coincidan con el código final. | **Pendiente** · hash: — · prueba: — |
| 20 | `Rodolfo` | `docs: Cerré la evidencia y el informe final` | Informe PDF de 8–12 páginas sugeridas con las diez secciones del punto 6, capturas, distribución **real** del trabajo, conclusiones, dificultades y respuestas para la defensa. Revisar paginación, enlaces y legibilidad. Integrar en `main`, repetir pruebas y **después** crear/subir la etiqueta anotada `v2.0`. | **Pendiente** · hash: — · prueba: — |

**Cierre obligatorio de fase 3:** una persona puede clonar el repositorio y ejecutar exactamente el README; el PDF abre correctamente y muestra las diez secciones; las evidencias corresponden a la aplicación real; los scripts SQL recrean la base; `main` y `v2.0` contienen la versión probada. Llevar la rúbrica impresa y preparar la demostración de cualquier módulo.

## Matriz final de requisitos del PDF

Marcar solo tras comprobar, no por existir un archivo.

| Requisito | Commits previstos | Estado / evidencia |
| --- | --- | --- |
| Mismo repositorio y consola de Fase 1 conservada | 3, 15 | ☐ Pendiente |
| MySQL/MariaDB: jerarquía, ventas/detalle, FK, restricciones, `schema.sql` y `seed.sql` con tres por tipo | 1, 2 | ☐ Pendiente |
| Repositorios PDO preparados, fábrica única y polimorfismo sin decisiones por tipo en vistas | 3, 4, 11, 14 | ☐ Pendiente |
| CRUD completo del catálogo y alta/listado de ventas | 4, 7, 9–13 | ☐ Pendiente |
| Ocho páginas HTML5, layout común, CSS propio y diseño móvil | 6, 7, 9, 10, 12–14, 18 | ☐ Pendiente |
| Formularios: HTML5 y PHP, errores por campo, valores conservados, PRG, flash, CSRF y borrado POST | 5, 6, 9, 10, 12, 15, 17 | ☐ Pendiente |
| Imágenes: MIME, 2 MB, nombre único, reemplazo, eliminación e imagen por defecto | 8–10, 17 | ☐ Pendiente |
| Venta transaccional y sin sobreventa; ticket/reporte desde DB | 11–15 | ☐ Pendiente |
| Escape de salida, secretos fuera de Git, sin frameworks PHP/CSS y etiquetas `[CONCEPTO]` completas y correctas | 2–17 | ☐ Pendiente |
| README reproducible, capturas, informe PDF completo, 20 commits reales y `v2.0` | 16–20 y revisión Git | ☐ Pendiente |

## Bitácora de ejecución

Actualizar después de **cada** commit y al cerrar cada fase. Anotar hechos, no intenciones. No guardar contraseñas, tokens ni cuentas en este archivo.

| Fecha/hora real | Fase y # | Rama | Autor real | Hash / enlace | Prueba pasada y resultado | Integrado en `main` |
| --- | --- | --- | --- | --- | --- | --- |
| 29/09 22:37 | 1 · #1 | `Miguel` | Miguel | `d763698` | Esquema importado en MySQL 8 | No |
| 29/09 22:45 | 1 · #2 | `Miguel` | Miguel | `9424127` | Tres semillas por tipo y PDO | No |
| 29/09 22:46 | 1 · #3 | `Miguel` | Miguel | `06009aa` | Consola y sintaxis PHP | No |
| 29/09 22:46 | 1 · #4 | `Miguel` | Miguel | `f00fcba` | CRUD e historial en MySQL | No |
| 29/09 22:51 | 1 · #5 | `Miguel` | Miguel | Último commit de `Miguel` | Modelo, validador y CRUD | No |

**Fase 1:** En curso: Miguel 5/5; Diego pendiente por indicación del usuario. MySQL 8, semillas, CRUD, historial y consola probados.
**Fase 2:** Pendiente. Resultado de pruebas y bloqueos: —
**Fase 3:** Pendiente. Resultado de pruebas y bloqueos: —
**Entrega:** `main`: — · etiqueta `v2.0`: — · informe PDF: — · URL pública: —

## Estado para retomar el trabajo

En `Miguel` están `database/schema.sql`, `database/seed.sql`, la conexión PDO, la fábrica, el repositorio de ítems y la validación. `tests/check_miguel.php` comprueba el modelo y, si existe `config/config.php`, también el CRUD y el historial en MySQL. La configuración real está ignorada por Git: copiar `config/config.example.php` y ajustar los datos locales. Importar primero el esquema y después las semillas. El test de base necesita esas nueve filas.

Diego debe partir del código de `Miguel` cuando el usuario indique iniciar su trabajo; **no hay commits de Diego todavía**. `main` sigue en la Fase 1 original hasta que se cierre la fase. Antes de los commits de otro integrante, cambiar `user.name` y `user.email` locales para que la autoría no herede la configuración de Miguel.
