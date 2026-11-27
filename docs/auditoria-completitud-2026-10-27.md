# Auditoría de completitud de Atlantia Supermarket

Fecha: 27 de octubre de 2026, America/Guatemala.

Actualización posterior: se corrigieron los avisos de Composer/npm y se añadieron protecciones de pagos, webhooks, transporte y Android. Los resultados actuales y los límites de validación están en [las correcciones de seguridad](C:/laragon/www/Atlantia_Supermarket/docs/seguridad-correcciones-2026-10-27.md). Las cifras y reproducciones de los apartados siguientes corresponden al diagnóstico anterior a esas correcciones.

**Dictamen: el proyecto tiene una base funcional considerable, pero todavía tiene defectos de negocio y pendientes de integración, seguridad y operación que impiden considerarlo terminado para producción.** No es necesario rehacerlo. Sí es necesario cerrar los puntos siguientes y demostrar el funcionamiento completo en un entorno de pruebas con los servicios reales.

No asigno un porcentaje: no hay una lista de requisitos aceptada contra la cual medir el 100 %. Una demostración académica, un piloto con pocos comercios y una operación pública tienen criterios de cierre distintos.

## 1. Alcance y resultados comprobados

Se revisaron la estructura del repositorio, los flujos y servicios principales de Laravel, autenticación y permisos, checkout, pagos y devoluciones, FEL, archivos, ML, Android, configuración, pruebas, CI, Docker, monitoreo y documentación. El inventario contiene 1,083 archivos versionados, incluidos 500 bajo `app`, 71 del microservicio ML y 68 de la aplicación móvil. El inventario no equivale a una validación manual de cada línea.

| Comprobación | Resultado | Alcance real |
|---|---|---|
| PHPUnit, configuración existente | **137 pruebas y 494 aserciones correctas** | SQLite en memoria; no se usó ni alteró MySQL |
| Compilación web | **Correcta**, Vite 8.1.0 | Generación de CSS/JS; no demuestra que los botones funcionen en un navegador |
| Registro de rutas Laravel | **307 rutas registradas** | Registro y resolución de rutas; no ejecución de cada pantalla |
| Configuración Docker de staging | **Válida** con `docker compose ... config --quiet` | Sintaxis y composición; no se levantaron contenedores |
| Auditoría Composer contra Packagist | **25 avisos en 7 paquetes** | Versiones fijadas en `composer.lock` |
| Auditoría npm contra su registro | **6 paquetes afectados: 5 de severidad alta y 1 crítica** | Los seis corresponden a dependencias de desarrollo/compilación |
| Auditoría de configuración de integraciones | **5 errores y 2 comprobaciones estructurales correctas** | Sin contactar proveedores ni enviar mensajes, facturas o cobros |
| Reproducción adicional de reembolso excesivo | **Defecto confirmado** | Un pago de Q28 admite una devolución de Q1,000 en SQLite |
| Sintaxis de Python ML | **49 archivos analizados sin errores sintácticos** | No sustituye la ejecución del microservicio |
| Suite Python y compilación Android de publicación | **No ejecutadas** | Faltan dependencias Python en el intérprete disponible; no se validó una compilación Android de lanzamiento |

La lectura inicial de permisos de archivos falló dentro del aislamiento de Windows. Las pruebas PHP se repitieron fuera de ese aislamiento, con SQLite en memoria y correo simulado, y terminaron correctamente. Ese fallo inicial no se atribuye al proyecto.

Durante esta revisión no estaban accesibles los servicios locales en los puertos de MySQL, Redis, Meilisearch, Laravel y staging comprobados. La auditoría Laravel devolvió error de conexión SQL 2002. **No se pudo afirmar el estado actual de migraciones, datos demo, roles reales ni permisos de usuarios guardados en MySQL.** Esto no significa que se hayan perdido los datos ni que falten las tablas: la conexión no estaba disponible.

## 2. Qué ya está implementado

| Área | Base existente | Qué falta para darla por cerrada |
|---|---|---|
| Clientes | Registro, acceso, recuperación, verificación de correo, catálogo, carrito, direcciones, pedidos, seguimiento, reseñas, favoritos y devoluciones | Recorrido real en navegador, correo efectivo, concurrencia y casos de error |
| Vendedores | Solicitud y aprobación, productos e imágenes, inventario, pedidos, comisiones, reportes y perfil fiscal | Prueba integral con comercios reales, archivos en producción y FEL real |
| Administración y empleados | CRUD, usuarios, roles, nómina, revisión de transferencias, logística, soporte, auditoría y observabilidad | Auditoría de los permisos existentes en la base real y aceptación operativa |
| Repartidores | API autenticada, ofertas, asignación, estados de entrega, código, ubicación, billetera, retiros y soporte | Firebase operativo, endurecimiento Android, compilación firmada y pruebas en equipos físicos |
| Pagos | Efectivo al entregar, tarjeta mediante POS al entregar y transferencia manual | Conciliación real, controles de reembolso y configuración de los medios habilitados |
| Facturación | Generación de documentos, certificador intercambiable, emulación, contingencia, correo y anulación | Validación contractual y de documentos con el certificador real |
| ML | API FastAPI, contratos, reglas antifraude/reseñas, pronóstico estadístico y recomendaciones heurísticas | Corregir entrenamiento, implementar modelos si son requisito y ejecutar su suite |
| Despliegue | Imágenes, staging, HTTPS con Caddy, workers, scheduler, scripts y workflows | Corregir inconsistencias y demostrar despliegue, observación y recuperación |

La presencia de estas piezas y las pruebas correctas son evidencia de avance; no prueban que todos los módulos hayan sido aceptados por sus usuarios.

## 3. Hallazgos y trabajos prioritarios

### A01. Dependencias con avisos de seguridad — antes de publicar

Composer reportó 25 avisos: 1 crítico, 10 altos, 11 medios, 2 bajos y 1 sin severidad indicada. Afectan a las versiones fijadas de Laravel, Livewire, Guzzle, CommonMark, Flysystem, phpseclib y Symfony HttpFoundation.

Entre ellas están Laravel 13.19.0 y Livewire 3.8.2. El aviso crítico de Laravel corresponde a TokenGuard; los guards revisados aquí utilizan sesión y Passport, por lo que **no se ha demostrado que ese bypass concreto sea explotable en esta aplicación**. Esto no elimina los otros avisos ni la necesidad de actualizar. El mantenedor identifica las versiones corregidas y las condiciones del problema en su [aviso oficial](https://github.com/laravel/framework/security/advisories/GHSA-f9x3-7vq9-cph6).

npm reportó seis paquetes afectados: `concurrently`, `nanoid`, `postcss`, `shell-quote`, `source-map-js` y `vite`. Todos están marcados como dependencias de desarrollo en el lockfile; no se debe presentar ese resultado como seis vulnerabilidades demostradas en el sitio público compilado. Aun así, afectan a la seguridad del entorno de desarrollo y construcción. npm informa correcciones disponibles.

**Cierre:** actualizar las dependencias de forma controlada, revisar compatibilidad y condiciones de explotación, repetir ambas auditorías, las pruebas y la compilación. No se actualizaron paquetes durante esta revisión. El workflow de backend ejecuta `composer audit`, por lo que estos resultados también afectan al cierre de CI.

### A02. Reembolso mayor al importe cobrado — defecto confirmado

La validación permite un importe hasta Q999,999.99, pero no lo limita al saldo reembolsable del pedido. El servicio tampoco hace esa comparación.

Se reprodujo mediante la ruta administrativa, con un usuario administrador y una base temporal: un pedido/pago de Q28 aceptó una devolución de Q1,000; la devolución quedó aprobada y el pago marcado como reembolsado. Se simularon eventos y no se transfirió dinero real.

Evidencia: [validación del importe](C:/laragon/www/Atlantia_Supermarket/app/Http/Requests/Admin/Devolucion/ResolverDevolucionRequest.php:30), [resolución de devoluciones](C:/laragon/www/Atlantia_Supermarket/app/Services/Pedidos/DevolucionService.php:106) y [servicio de reembolso](C:/laragon/www/Atlantia_Supermarket/app/Services/Pagos/PasarelaPagoService.php:214).

**Cierre:** validar el saldo efectivamente cobrado y todavía reembolsable dentro de la transacción, impedir importes excesivos y duplicados, definir devoluciones parciales y exigir evidencia de la devolución de dinero manual. Para efectivo/POS, cambiar un estado en la base no acredita por sí mismo que se devolvió dinero al cliente. Añadir pruebas que rechacen el caso reproducido.

### A03. Entrenamiento ML incompleto y contrato roto — requiere código

La ruta envía tres argumentos a Celery, mientras la tarea recibe un solo `payload`. Además, la tarea intenta leer `response.job_id`, pero el esquema de respuesta define `job_uuid`.

El análisis de firmas reprodujo `too many positional arguments`. No se necesitó ejecutar ni modificar el broker para comprobar esa incompatibilidad.

Más allá de esos errores, `start_training` genera una respuesta con estado `queued`; no ajusta un modelo, no persiste un artefacto ni completa un ciclo de entrenamiento. `list_models` devuelve dos modelos fijos `fallback-1` con métricas en cero. El pronóstico actual usa media móvil y estacionalidad sencilla; recomendaciones y antifraude usan heurísticas/reglas.

Evidencia: [ruta de entrenamiento](C:/laragon/www/Atlantia_Supermarket/ml-service/app/api/v1/routes/training.py:14), [tarea Celery](C:/laragon/www/Atlantia_Supermarket/ml-service/app/workers/tasks.py:17), [campo incorrecto](C:/laragon/www/Atlantia_Supermarket/ml-service/app/workers/tasks.py:26) y [servicio de entrenamiento](C:/laragon/www/Atlantia_Supermarket/ml-service/app/domain/services/training_service.py:17).

**Cierre:** alinear los contratos, ejecutar un entrenamiento real si está dentro de los requisitos, registrar artefactos/versiones y métricas reales, actualizar el estado del trabajo y comprobar que la inferencia carga el modelo correcto. Instalar un entorno Python aislado y ejecutar su suite. Fijar las dependencias: actualmente hay rangos abiertos y no se encontró un lockfile Python versionado.

Es válido lanzar un piloto con reglas y media móvil si se acuerda ese alcance. En ese caso hay que describirlo de esa manera y no dar por terminado un sistema de aprendizaje/reentrenamiento automático.

### A04. Archivos públicos incoherentes con el despliegue — requiere código/configuración

El flujo de categorías guarda imágenes explícitamente en el disco `public`, que está configurado con driver local. Cambiar `FILESYSTEM_DISK=s3` no cambia ese disco explícito. El catálogo también construye algunas URL como `/storage/...`.

En producción, el volumen de almacenamiento se monta en los servicios PHP; Nginx sirve otra imagen y no comparte ese volumen. La imagen web excluye `public/storage`. Por tanto, con la configuración entregada no hay un recorrido consistente para servir esas nuevas imágenes locales desde Nginx. Los productos sí tienen una selección explícita de S3, de modo que el problema no afecta de la misma manera a todos los flujos.

Evidencia: [disco público](C:/laragon/www/Atlantia_Supermarket/config/filesystems.php:43), [imágenes de categorías](C:/laragon/www/Atlantia_Supermarket/app/Services/Catalogo/CategoriaService.php:44), [volumen PHP](C:/laragon/www/Atlantia_Supermarket/docker-compose.prod.yml:40) y [servicio Nginx](C:/laragon/www/Atlantia_Supermarket/docker-compose.prod.yml:68).

**Cierre:** elegir almacenamiento público en S3/CDN con URL generada por el disco, o un volumen compartido y servido correctamente. Aplicarlo a categorías, banners, logos y demás cargas, migrar archivos existentes y probar cargas nuevas desde la imagen de producción. Mantener aparte los documentos privados con autorización de acceso.

### A05. Tiempos de las colas inconsistentes — riesgo operativo

Redis usa `retry_after=90` por defecto. Los workers de producción permiten trabajos de 120 y 180 segundos, y la plantilla de producción no corrige ese valor. Un trabajo suficientemente largo puede volver a estar disponible mientras sigue ejecutándose.

Evidencia: [reintento Redis](C:/laragon/www/Atlantia_Supermarket/config/queue.php:71) y [workers](C:/laragon/www/Atlantia_Supermarket/docker-compose.prod.yml:108). La [documentación de Laravel](https://laravel.com/framework/docs/13.x/queues#job-expiration) exige que el timeout del worker sea menor que `retry_after` para evitar ejecuciones duplicadas.

**Cierre:** alinear los tiempos efectivos de cada trabajo/worker/conexión, dejar margen y verificar idempotencia de notificaciones, facturas, inventario y efectos financieros. Probar concurrencia y caída/reinicio de un worker. El riesgo se refiere a la configuración proporcionada; no se observó un trabajo duplicado real.

### A06. Compatibilidad de JavaScript con CSP de producción — riesgo identificado por código

La política permite `unsafe-eval` únicamente en local. Los layouts cargan el paquete habitual de Livewire y usan expresiones Alpine `x-data`. El paquete instalado contiene constructores `Function`/`AsyncFunction` para evaluar expresiones. Alpine documenta esta incompatibilidad y dispone de una [variante compatible con CSP](https://alpinejs.dev/advanced/csp).

Evidencia: [política de scripts](C:/laragon/www/Atlantia_Supermarket/app/Http/Middleware/SecurityHeaders.php:87), [excepción local](C:/laragon/www/Atlantia_Supermarket/app/Http/Middleware/SecurityHeaders.php:112), [carga en layout](C:/laragon/www/Atlantia_Supermarket/resources/views/layouts/guest.blade.php:46) y [evaluador instalado](C:/laragon/www/Atlantia_Supermarket/vendor/livewire/livewire/dist/livewire.esm.js:1807).

**Cierre:** reproducir con `APP_ENV=production` en navegador y resolver la compatibilidad de la versión de Livewire/Alpine y los componentes. No basta con que el HTML devuelva 200 ni con añadir un nonce. Este hallazgo es una inferencia de código y documentación; no se ejecutó un navegador contra producción durante esta revisión.

### A07. Integraciones reales pendientes — configuración y aceptación

El chequeo sin conexiones externas produjo:

| Integración | Resultado actual | Pendiente |
|---|---|---|
| FEL INFILE | Error | Cuenta/credenciales, secreto de webhook y desactivar mock; revisar configuración por vendedor |
| Almacenamiento S3 | Error | Bucket, credenciales o identidad de instancia y conexión de discos públicos/privados |
| Firebase | Error | Habilitación, proyecto, cuenta de servicio y clave privada |
| Soporte | Error | Correo, teléfono, emergencia y WhatsApp operativos |
| Terminales POS | Error | Identificadores de comercio/terminales y contacto de soporte |
| SMTP | Configuración estructural correcta | Autenticación, entrega efectiva y manejo de fallos todavía no verificados |
| Mapbox/Google Maps | Configuración estructural correcta | Validez de claves, restricciones, cobertura y rutas reales todavía no verificadas |

**Cierre FEL:** comprobar el contrato y el XML con el certificador real, certificar/anular un documento de prueba autorizado y verificar rechazo, contingencia y correo. El generador actual construye un XML base; las pruebas con mock no acreditan aceptación fiscal real. No se emitieron documentos fiscales durante la auditoría.

**Cierre pagos:** acordar el alcance. Hoy tarjeta significa **POS al entregar**, no un cobro en línea integrado al checkout. Si ese es el modelo aprobado, hay que cerrar la conciliación de POS, efectivo y transferencias. Si se requiere pago en línea, falta implementar y aceptar ese recorrido. El entrypoint todavía exige claves Stripe; hay que alinear ese requisito heredado con el modelo elegido, en lugar de rellenarlo con valores ficticios.

Los probes incorporados tampoco son aceptación integral: INFILE prueba que una URL responde y SMTP abre una conexión TCP, no una factura certificada ni un correo recibido. Ver [probes de integración](C:/laragon/www/Atlantia_Supermarket/app/Services/Integrations/ProductionIntegrationReadinessService.php:373).

### A08. Android todavía usa configuración de desarrollo — antes de distribuir

Hay código real de GPS en segundo plano, dispositivo, ofertas y notificaciones, y existe un APK debug local. Sin embargo:

- Capacitor y el manifiesto permiten tráfico HTTP sin cifrar.
- El token del servicio de ubicación se guarda en `SharedPreferences` normales; `MODE_PRIVATE` limita el acceso de otras apps, pero no es un mecanismo de cifrado explícito del valor.
- No hay `signingConfig` de release en el Gradle revisado. No se validó firma por un mecanismo externo.
- No se encontró `android/app/google-services.json` en esta copia; el Gradle permite continuar sin él y avisa que push no funcionará.
- No se encontraron pasos de CI para probar/compilar la aplicación móvil.

Evidencia: [HTTP en Capacitor](C:/laragon/www/Atlantia_Supermarket/mobile/repartidor/capacitor.config.ts:10), [manifiesto](C:/laragon/www/Atlantia_Supermarket/mobile/repartidor/android/app/src/main/AndroidManifest.xml:26), [persistencia del token GPS](C:/laragon/www/Atlantia_Supermarket/mobile/repartidor/android/app/src/main/java/com/atlantia/delivery/repartidor/tracking/LocationForegroundService.java:143) y [release Gradle](C:/laragon/www/Atlantia_Supermarket/mobile/repartidor/android/app/build.gradle:20).

**Cierre:** separar debug/release, fijar dominio HTTPS, deshabilitar HTTP en release, proteger el token con un mecanismo respaldado por Android Keystore, configurar Firebase y firma/versionado. Probar en teléfonos reales: permisos denegados, pantalla bloqueada, batería, pérdida de internet, cierre/reapertura y notificaciones con la app en segundo plano.

### A09. Monitoreo y recuperación necesitan cierre operativo

Prometheus está configurado para leer `/health` y `/api/v1/health`, pero los endpoints de la aplicación devuelven JSON. Son comprobaciones de salud, no métricas en el [formato de exposición que acepta Prometheus](https://prometheus.io/docs/instrumenting/exposition_formats/).

Evidencia: [scrapes configurados](C:/laragon/www/Atlantia_Supermarket/monitoring/prometheus/prometheus.yml:12), [salud Laravel](C:/laragon/www/Atlantia_Supermarket/app/Http/Controllers/HealthController.php:23) y [salud ML](C:/laragon/www/Atlantia_Supermarket/ml-service/app/api/v1/routes/health.py:14).

**Cierre monitoreo:** agregar endpoints/exporters adecuados o un blackbox exporter para salud HTTP; comprobar que los dashboards reciban datos y que las alertas lleguen a un responsable. La presencia de archivos Grafana/Prometheus no demuestra monitoreo operativo.

Hay un script de respaldo MySQL con retención y subida opcional a S3. No se encontró en el repositorio la programación automática de ese script ni un procedimiento automatizado de restauración/rollback. No se verificó si existen tareas configuradas fuera del repositorio.

**Cierre recuperación:** programar y observar los respaldos, incluir los archivos y secretos necesarios para recuperar el servicio, verificar restauración en un entorno aislado y definir cuánto dato/tiempo de caída se puede aceptar. La importación local realizada anteriormente no sustituye una prueba de recuperación del despliegue de producción.

### A10. Reintentos de webhooks tras fallos — revisar antes de integrar proveedores

El middleware guarda el ID del evento como usado **antes** de ejecutar el controlador. Si el procesamiento falla, un reintento con el mismo ID se considera repetido y devuelve 409 durante el TTL de diez minutos. La protección contra replay existe, pero falta separar bloqueo temporal, procesamiento satisfactorio y reintento tras error.

Evidencia: [registro previo a procesar](C:/laragon/www/Atlantia_Supermarket/app/Http/Middleware/VerificarWebhookHmac.php:49) y [llamada al controlador](C:/laragon/www/Atlantia_Supermarket/app/Http/Middleware/VerificarWebhookHmac.php:53).

**Cierre:** persistir recepción/estado y resultado de eventos, conservar idempotencia por operación de negocio, liberar o reintentar fallos y responder según el contrato del proveedor. Añadir pruebas de fallo 500 seguido de reintento y de dos entregas simultáneas. Hallazgo de lectura de código; no se enviaron webhooks a proveedores.

### A11. La automatización no cubre todo el producto

La suite por defecto incluye `tests/Unit` y `tests/Feature`. El archivo bajo `tests/Browser` queda fuera y, pese a su nombre, usa pruebas HTTP y no un navegador con JavaScript. Los workflows revisados ejecutan Laravel, pero no la suite Python ni pruebas Android ni recorridos de navegador.

Los comandos propios de RBAC, datos demo y alistamiento operativo existen, pero no están encadenados al script `preflight.sh` que autoriza el despliegue. La auditoría de operación sigue declarando obligatorias las pruebas manuales.

Evidencia: [selección de pruebas](C:/laragon/www/Atlantia_Supermarket/phpunit.xml:9), [prueba denominada Browser](C:/laragon/www/Atlantia_Supermarket/tests/Browser/ClienteCheckoutFlowTest.php:12), [CI backend](C:/laragon/www/Atlantia_Supermarket/.github/workflows/backend-ci.yml:75), [release](C:/laragon/www/Atlantia_Supermarket/.github/workflows/backend-release-images.yml:85) y [preflight](C:/laragon/www/Atlantia_Supermarket/scripts/production/preflight.sh:1).

**Cierre:** integrar pruebas Python y Android, auditorías de datos/permisos, revisión de dependencias y flujos reales de navegador en los controles de entrega. Ejecutar también integración sobre MySQL/Redis: SQLite no reproduce todos sus bloqueos, concurrencia ni diferencias SQL. No se calculó cobertura de código.

### A12. Falta una aceptación funcional y operativa acordada

Hay manuales y documentación técnica útiles. Falta evidencia revisable de una corrida completa con cliente, comercio, repartidor y administración, con responsables que acepten los resultados. También hay que confirmar qué módulos son obligatorios para la primera entrega, incluidos nómina, FEL y ML.

No se identificaron rutas públicas dedicadas a términos y privacidad en los archivos de rutas revisados. Deben definirse contenido, publicación y aceptación junto con las reglas de devoluciones, cancelaciones, horarios, cobertura, gastos de envío y atención. Esto es un pendiente de producto/operación; no una determinación de obligaciones legales para una jurisdicción concreta.

## 4. Orden de cierre recomendado

| Etapa | Trabajo | Evidencia para terminarla |
|---|---|---|
| 1. Integridad y seguridad | Resolver reembolsos, actualizar dependencias y revisar autorización/secretos del entorno final | Caso Q1,000/Q28 rechazado, auditorías revisadas y pruebas correctas |
| 2. Entorno reproducible | Levantar staging aislado, verificar MySQL/Redis/colas/buscador/ML, corregir archivos y tiempos | Arranque desde cero documentado, servicios saludables, cargas públicas y privadas correctas |
| 3. Alcance comercial | Cerrar soporte, POS/transferencias, SMTP, mapas, Firebase y FEL según requisitos | Pruebas controladas y resultados por proveedor; sin depender de mocks para funciones anunciadas como reales |
| 4. Producto completo | Corregir/completar ML, endurecer Android, comprobar CSP y navegador | Entrenamiento o alcance heurístico aceptado, APK release firmado y flujos visuales correctos |
| 5. Lanzamiento | CI integral, monitoreo, backup/restauración y piloto con todos los actores | Evidencias de compra-entrega-cobro-reembolso, alertas, recuperación y aprobación de responsables |

Una primera entrega puede excluir módulos opcionales si se acuerda y refleja en el producto y sus manuales. Eso reduce alcance; no sustituye corregir los errores financieros y de seguridad de las funciones que sí se habiliten.

## 5. Lista de aceptación para considerar la entrega completa

- [ ] Requisitos de la primera entrega acordados y cada módulo clasificado como obligatorio u opcional.
- [ ] Registro, verificación, recuperación y acceso por cada rol comprobados con correo recibido.
- [ ] Roles y permisos de la base real auditados, incluidos cruces de cuentas/vendedores y 2FA administrativo.
- [ ] Datos demo revisados y separados de los usuarios/comercios reales.
- [ ] Catálogo, búsqueda, imágenes, carrito invitado y autenticado probados en móvil y escritorio.
- [ ] Dos clientes compitiendo por el último stock no producen sobreventa.
- [ ] Compra multivendedor con totales, envío, descuento y estados correctos.
- [ ] Pago/conciliación según los métodos realmente habilitados, con evidencia y responsables.
- [ ] Cancelación, reintento y devolución parcial/total sin importes excesivos ni doble devolución.
- [ ] Comercio acepta, prepara, rechaza y entrega a logística con trazabilidad.
- [ ] Repartidor recibe push, acepta, recoge, ubica al cliente y cierra con código válido.
- [ ] GPS, reconexión, cierre/reapertura de la app y permisos Android comprobados en dispositivos.
- [ ] Billetera, retiros, liquidaciones de efectivo y soporte conciliados contra pedidos de prueba.
- [ ] Documentos privados inaccesibles sin autorización; imágenes públicas disponibles desde el despliegue final.
- [ ] FEL real certifica/anula y gestiona contingencias si forma parte del alcance aprobado.
- [ ] ML entrena y sirve versiones reales, o sus heurísticas y limitaciones quedan expresamente aceptadas.
- [ ] Interacciones JavaScript funcionan con la CSP y configuración exactas de producción.
- [ ] Pruebas Laravel, Python, navegador y Android necesarias pasan en CI.
- [ ] Pruebas de carga y concurrencia cumplen el objetivo acordado para el piloto.
- [ ] Auditorías de dependencias revisadas y sin vulnerabilidades bloqueantes pendientes.
- [ ] HTTPS, configuración final, cuentas de servicio, cookies y secretos listos para producción.
- [ ] Workers y scheduler se reinician, no duplican efectos y generan alertas útiles ante fallos.
- [ ] Métricas reales alimentan dashboards y alertas con un responsable de respuesta.
- [ ] Un respaldo programado se restaura correctamente en un entorno aislado y se mide el tiempo.
- [ ] Despliegue y rollback ensayados; políticas, manuales y contactos operativos publicados.
- [ ] Piloto completo aceptado por cliente, vendedor, repartidor y administrador.

## 6. Evidencia y límites

Los resultados detallados están guardados en la carpeta privada [auditoria-2026-10-27](C:/laragon/www/Atlantia_Supermarket/storage/app/private/auditoria-2026-10-27). Incluye logs/JUnit de las 137 pruebas, auditorías de Composer/npm, configuración sin secretos, rutas, inventario, prueba de firma ML y reproducción del reembolso excesivo.

La prueba adicional documenta que el comportamiento defectuoso existe; que esa reproducción pase no significa que el reembolso sea correcto. El primer fallo de preparación de esa reproducción se corrigió en el fixture antes de ejecutarla satisfactoriamente.

No se cambiaron servicios, reglas de negocio, credenciales ni datos existentes para emitir este diagnóstico. Se generaron evidencias privadas y este documento; la compilación regeneró los assets ignorados de Vite. No se enviaron correos/push, cobros, facturas ni comunicaciones externas. Las consultas de seguridad solo enviaron información de dependencias a los registros correspondientes.

Quedaron sin verificar: estado de la base actual, salud de un despliegue de producción, firma y ejecución Android en un teléfono, pruebas Python completas, entrega real de proveedores, carga/penetración y comportamiento visual en navegador. No deben confundirse con módulos que necesariamente faltan: algunos pueden existir fuera de esta copia o requerir únicamente configuración y pruebas.
