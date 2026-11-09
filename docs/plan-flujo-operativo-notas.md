# Plan – Flujo operativo: alistamiento, despacho y consumo de equipos quirúrgicos

Fase 0 (análisis). Este documento no incluye código ni migraciones ejecutadas.
Base: el código de `main` (commit `9c2e72b`). `docs/modulo-notas-entrega.md` y `CLAUDE.md` no existen en el repositorio,
así que los "estados documentados" se toman de los comentarios de las migraciones.

---

## 0. Qué existe hoy (hallazgos)

### 0.1 Tablas que ya cubren el flujo (solo existen como tablas: no hay modelos, controladores ni vistas)

| Tabla | Uso actual | Comentario |
|---|---|---|
| `notas_entrega` | Cabecera: número (char 12, QR), institución, regional, bodega origen, fechas, estado | Faltan tipo de cirugía, médico, paciente, instrumentador, preparador y fecha de cirugía |
| `nota_entrega_detalles` | Líneas: `tipo_linea` EQUIPO/IMPLANTE/INSTRUMENTAL/ACTIVO/ADICIONAL, cantidades enviada/recibida/consumida/faltante, precio y subtotal | Base muy buena: se extiende |
| `consumos` | Registro del instrumentador con cadena de correcciones (`consumo_original_id`) | Se reutiliza tal cual para el registro clínico |
| `recepciones` y `recepcion_detalles` | Devolución a bodega con `resultado` DEVUELTO/DAÑADO/FALTANTE… | Se reutiliza para la devolución verificada |
| `reposiciones` y `reposicion_detalles` | Reposición del contenido del equipo tras consumo | Fuera del alcance inmediato; se conecta después |
| `movimientos_inventario` | **Solo cabecera**: tipo, bodega/equipo origen-destino, nota, usuario | **No tiene producto, lote, cantidad ni saldo** |
| `stock_lotes` | Stock por bodega/ubicación/lote con `cantidad_reservada` y `cantidad_disponible` calculada | **Ningún código la llena hoy** |
| `equipo_detalles` | Composición maestra (`cantidad_configurada`, `tipo_contenido`, grupo/bandeja/orden) | La fuente de "Cant. estándar" |
| `equipo_inventario` | **Contenido físico real de cada equipo, por lote** (se llena desde el Excel de equipos) | Ver la decisión D1 |
| `activos` | Motores y baterías con serie; estados DISPONIBLE/EN_EQUIPO/EN_CIRCULACION/EN_MANTENIMIENTO/BAJA | Se usa tal cual |
| `roles`, `permissions`, `permission_role`, `role_user` | RBAC propio; middleware `permiso:xxx`; `User::tienePermiso()` | No hay Policies ni Gates |

### 0.2 Cómo se distingue hoy el tipo de ítem
Ya existe un campo: `equipo_detalles.tipo_contenido` y `nota_entrega_detalles.tipo_linea`, con los valores
`IMPLANTE | INSTRUMENTAL | ACTIVO | ADICIONAL` (más `EQUIPO` en la nota). Además existen
`requiere_lote` y `requiere_serial`, y las FK separadas `producto_id`, `instrumental_id` y `activo_id`.
**No se propone ningún campo nuevo para esto**; se formaliza en un enum de PHP (`TipoItem`).

### 0.3 Problemas encontrados (no los corrijo sin autorización)

1. **`migrate:fresh` falla**: `2026_09_11_213601_add_campos_to_instrumentales_table` vuelve a agregar columnas
   que ya crea la migración anterior. En MySQL también fallaría
   `2026_08_31_004851_add_foreign_keys_to_movimientos_inventario` (FK duplicadas), y
   `2026_10_14_173152` usa `ALTER TABLE … MODIFY`, que solo existe en MySQL. Producción se construyó de forma no lineal.
   → Hoy no se pueden correr pruebas con `RefreshDatabase`.
2. **Credenciales en el repositorio**: `tests/t.txt` contiene un usuario y una contraseña. Recomiendo cambiar esa contraseña y borrar el archivo.
3. **Rutas sin permiso**: la importación y el preview de productos y lotes solo exigen estar logueado (o ni eso: las rutas de productos
   no tienen `auth`).
4. `SiigoService` en realidad consume la API de **Contífico** (solo es un problema de nombre).
5. No existe ningún catálogo de **precios por institución**, ni de **médicos**, **pacientes** o **tipos de cirugía**
   (`productos.tipo_cirugia` es solo texto libre).
6. No existe un módulo de administración de equipos (solo el importador); la "modificación permanente de la composición" no
   tiene pantalla donde colgarse.
7. `tienePermiso()` hace una consulta por cada llamada; con Policies se repite mucho → cachear por request.

---

## 1. Diferencias entre el prompt y el modelo actual, con propuesta de unificación

### 1.1 Estados de la nota

| Comentario en la migración | Prompt | **Propuesta unificada** | Efecto en inventario |
|---|---|---|---|
| BORRADOR | BORRADOR | `BORRADOR` | ninguno |
| — | ALISTAMIENTO | `EN_ALISTAMIENTO` | reserva |
| PREPARADA | (fin del alistamiento) | `ALISTADA` (lista para despachar; todos los equipos están completos o tienen faltantes aceptados) | reserva |
| DESPACHADA | DESPACHADA | `DESPACHADA` | transferencia a consignación |
| RECIBIDA | ENTREGADA | `ENTREGADA` (*) | — |
| EN_CIRUGIA | — | **se elimina** (el consumo se registra con la nota ENTREGADA) | — |
| — | "pasa a conciliación" | `EN_CONCILIACION` (consumo enviado, en solo lectura) | consumo |
| EN_DEVOLUCION | (retorno) | `EN_DEVOLUCION` (material de vuelta, en verificación) | devolución / cuarentena |
| CERRADA | CERRADA | `CERRADA` | — |
| ANULADA | ANULADA | `ANULADA` | reversa si ya se despachó |

(*) Uso `ENTREGADA` y no `RECIBIDA` porque `recepciones` ya significa "ORTOMAX recibe la devolución"; así se evita la ambigüedad.
La columna es `string(30)` y no hay datos ni código que dependan de ella → **no requiere migración**, solo un enum de PHP
`EstadoNotaEntrega` con la tabla de transiciones permitidas.

### 1.2 ⚠️ D1 – De dónde sale el contenido del equipo (la decisión más importante)

El prompt dice que "Lote" y "Vence" vienen de `stock_lotes` con FEFO. Pero el sistema ya modela que **cada equipo
tiene sus implantes cargados físicamente** (`equipo_inventario`, por lote), y existen `reposiciones` para rellenarlo
después de una cirugía. Así trabajan normalmente los equipos de osteosíntesis.

**Propuesta:**
- **Contenido propio del equipo** → sale de `equipo_inventario` (lote real que está en la caja). "Cant. alistada" se
  propone igual al contenido físico; la reserva se hace en `equipo_inventario.cantidad_reservada`.
- **Completar faltantes o agregar extras** → sale de `stock_lotes` de la bodega origen con **FEFO** (aquí sí aplica la regla del prompt).
  La línea queda marcada `origen = BODEGA`.
- Diferencias contra `equipo_detalles.cantidad_configurada` → se resaltan como faltante o sobrante.

Si en la operación real los equipos se arman desde cero en cada despacho (sin stock propio), todo saldría de
`stock_lotes` y `equipo_inventario` dejaría de usarse para implantes. **Necesito que confirmes cuál es el caso.**

### 1.3 D2 – Equipos equivalentes ("N.1" / "N.2")
`equipos.codigo_referencia` parece agrupar las copias físicas de un mismo modelo de equipo. Propuesta: la plantilla de cirugía
apunta a la **referencia** (el modelo) y el sistema elige una copia física disponible; si la preferida está ocupada,
ofrece otra copia con la misma referencia. Confírmame que ese es el significado del campo.

### 1.4 D3 – Ubicación "En institución / consignación"
`stock_lotes` exige `bodega_id` y `ubicacion_id`. Propuesta: una bodega `tipo_bodega = CONSIGNACION` por regional, con una
**ubicación por institución** (`ubicaciones.tipo = CONSIGNACION`) creada automáticamente la primera vez. La nota queda
en cada movimiento, así que la trazabilidad por nota no depende de la ubicación. Además: ubicaciones `CUARENTENA` y `BAJA`
en la bodega origen.
Para el contenido propio del equipo (D1), el equipo completo viaja: `equipo_inventario` no cambia de lugar; el movimiento
registra `equipo_origen_id`, y la ocupación del equipo se deduce de la nota activa.

### 1.5 D4 – Stock inicial
Como `stock_lotes` está vacía, el FEFO para completar faltantes no tendrá datos hasta que se carguen saldos iniciales
(¿desde Contífico o desde Excel?). Esto se puede hacer en una fase aparte; mientras tanto las pruebas usan datos de prueba (factories).

---

## 2. Cambios de base de datos (todos aditivos)

### 2.1 Tablas nuevas

| Tabla | Columnas clave | Para qué |
|---|---|---|
| `tipos_cirugia` | codigo, nombre, region_anatomica, activo | Catálogo ("Radio distal") |
| `tipo_cirugia_equipos` | tipo_cirugia_id, `codigo_referencia` (D2) o equipo_id, orden, obligatorio, observacion | Plantilla de sugerencia |
| `medicos` | identificacion, nombre, especialidad, telefono, activo | Catálogo mínimo |
| `nota_entrega_pacientes` | nota_entrega_id (1:1), identificacion, nombres, historia_clinica, aseguradora — **con casts `encrypted`** | Datos sensibles aparte de la nota, con acceso controlado y auditado |
| `precios_institucion` | producto_id, institucion_id **nullable (NULL = precio general)**, concepto `VENTA`/`ALQUILER`, precio, vigente_desde, vigente_hasta | Condiciones comerciales; el precio vigente se busca por la fecha de la nota |
| `movimiento_inventario_detalles` | movimiento_id, nota_entrega_detalle_id, producto_id, lote_id, activo_id, instrumental_id, cantidad, bodega/ubicación origen y destino, equipo origen y destino, `saldo_origen_resultante`, `saldo_destino_resultante` | Lo que hoy le falta al kardex |
| `consumos_facturables` | nota, institución, paciente (snapshot mínimo cifrado), médico, fecha_cirugia, equipo origen, producto, código, lote, vencimiento, cantidad, **precio_unitario y total congelados**, estado `PENDIENTE_FACTURAR`/`FACTURADO`/`ANULADO`, numero_factura, registrado_por, aprobado_por, anulado_por, motivo_anulacion, `exportado_at` y `referencia_externa` (para la futura integración con Contífico) | Tabla de facturación |
| `bitacora` | evento, auditable_type/id, nota_entrega_id, usuario_id, valores_anteriores (json), valores_nuevos (json), motivo, ip, user_agent, created_at (**sin updated_at**) | Auditoría inmutable (el modelo bloquea update/delete; en MySQL, además, triggers que lo impiden) |
| `configuraciones` (opcional) | clave, valor | Umbral de "próximo a vencer" (por defecto 90 días); si prefieres, va en `config/ortomax.php` |

### 2.2 Columnas nuevas en tablas existentes

- `notas_entrega`: tipo_cirugia_id, medico_id, instrumentador_id, preparador_id, solicitado_por_id, creado_por_id,
  fecha_cirugia_programada, fecha_cirugia_efectiva, fecha_entrega, fecha_envio_consumo, recepcion_firmada_por (texto),
  `firma_path` y `version` (para bloqueo optimista).
- `nota_entrega_detalles`: `nota_entrega_detalle_padre_id` (línea EQUIPO → sus ítems), instrumental_id,
  `equipo_inventario_id` y `stock_lote_id` (de dónde se reservó), `origen` (EQUIPO/BODEGA), `cantidad_estandar`,
  `cantidad_devuelta`, `cantidad_danada`, `es_modificacion` (resalta las diferencias), `precio_es_general` (bool),
  `estado_alistamiento` (PENDIENTE/COMPLETO/CON_FALTANTES, solo en la línea EQUIPO).
  Se reutilizan `cantidad_enviada` (= alistada) y `cantidad_consumida`.
- `movimientos_inventario`: `idempotency_key` (unique), `movimiento_reversado_id`.
- `consumos`: `adjunto_path`, `codigo_escaneado`, `cliente_uuid` (unique, para el envío sin duplicados y el modo offline futuro).
- Sin cambios de esquema: `lotes.estado` y `stock_lotes.estado` aceptan ACTIVO/INACTIVO/CUARENTENA/VENCIDO; `equipos.estado`
  acepta EN_MANTENIMIENTO; `ubicaciones.tipo` acepta CONSIGNACION/CUARENTENA/BAJA (todos son columnas string).

---

## 3. Servicios (app/Services/NotasEntrega/…)

| Servicio | Responsabilidad |
|---|---|
| `TransicionEstadoNota` | Valida la transición según el enum, ejecuta los efectos y escribe en la bitácora |
| `SugeridorEquipos` | Plantilla → copias físicas disponibles; si una está ocupada, indica nota e institución y ofrece una equivalente. No escribe nada |
| `OcupacionEquipos` | Determina si un equipo está libre (sin nota activa y no EN_MANTENIMIENTO) |
| `ContenidoEquipoNota` | Arma la vista estándar vs. alistada (equipo_detalles + equipo_inventario + líneas de la nota) con precios y totales |
| `ResolvedorPrecios` | Precio de la institución vigente, o el general marcado como tal |
| `AsignadorLotesFefo` | FEFO sobre stock_lotes; excluye lotes vencidos, inactivos o en cuarentena; alerta los próximos a vencer |
| `ModificadorContenidoNota` | Agregar, quitar, cambiar cantidad o lote con motivo obligatorio; solo en BORRADOR/EN_ALISTAMIENTO |
| `ServicioReservas` | Reserva y libera con `lockForUpdate` sobre stock_lotes y equipo_inventario |
| `MotorInventario` | **Único punto que cambia saldos**: crea el movimiento y su detalle, actualiza los saldos y guarda el saldo resultante; es idempotente por `idempotency_key` |
| `ServicioDespacho` | Revalida dentro de la transacción y hace la transferencia a consignación (idempotente) |
| `RegistroConsumo` | Valida que no supere lo entregado, guarda `consumos`, hace la salida definitiva, genera `consumos_facturables` con precio congelado y pasa la nota a EN_CONCILIACION |
| `ServicioDevolucion` | Usa `recepciones`: devuelto → bodega origen (mismo lote); dañado → cuarentena; cierre si `entregado = consumido + devuelto + dañado + pendiente` |
| `ServicioAnulacion` | Con permiso; reversa los movimientos en orden inverso (`movimiento_reversado_id`) |
| `Kardex` | Consulta por producto y por lote, con la nota de cada movimiento |

---

## 4. Roles, permisos y Policies

Se mantiene el RBAC propio (tablas `permissions` y `roles`), y las **Policies** lo consultan junto con el estado de la nota y la asignación:
`NotaEntregaPolicy`, `ConsumoPolicy`, `ConsumoFacturablePolicy`, `EquipoPolicy`, `TipoCirugiaPolicy`, `PacientePolicy`.

Permisos nuevos (se siembran con una migración de datos, igual que `add_instituciones_permissions`):
`notas.ver`, `notas.ver_todas`, `notas.crear`, `notas.alistar`, `notas.despachar`, `notas.confirmar_entrega`,
`notas.anular`, `notas.ajustar`, `consumos.registrar`, `consumos.corregir`, `devoluciones.verificar`, `bajas.aprobar`,
`equipos.composicion.editar`, `plantillas_cirugia.editar`, `facturacion.ver`, `facturacion.facturar`, `facturacion.anular`,
`pacientes.ver`, `kardex.ver`.

| Rol | Permisos |
|---|---|
| GERENTE | notas.crear, notas.ver_todas, notas.anular, notas.ajustar, kardex.ver, pacientes.ver |
| PREPARADOR | notas.ver, notas.crear, notas.alistar, notas.despachar |
| INSTRUMENTADOR | notas.ver (solo las asignadas, regla de la Policy), notas.confirmar_entrega, consumos.registrar, pacientes.ver (solo de sus notas) |
| JEFE_BODEGA | equipos.composicion.editar, devoluciones.verificar, bajas.aprobar, kardex.ver, notas.ver_todas |
| FACTURACION | facturacion.ver, facturacion.facturar, facturacion.anular (los datos del paciente los ve solo en la vista de facturación, enmascarados) |
| ADMINISTRADOR | plantillas_cirugia.editar y configuración (los roles existentes no se tocan; el rol id 1 recibe todo) |

Cada acción sensible se valida en el servidor con `$this->authorize()`; ocultar botones es solo cosmética.

---

## 5. Rutas y pantallas (Blade + JS sin framework, siguiendo el layout actual)

| Pantalla | Rutas (prefijo `/notas-entrega`) |
|---|---|
| Listado de notas con filtros por estado, institución y fecha | `GET /` |
| Crear o editar el borrador (datos incompletos permitidos; tipo de cirugía, médico, instrumentador) | `GET /crear`, `POST /`, `PUT /{nota}` |
| **Alistamiento**: equipos de la nota con estado y total; "Agregar equipo" con búsqueda; panel de sugerencias para aceptar o descartar | `GET /{nota}/alistamiento`, `GET /{nota}/sugerencias`, `POST /{nota}/equipos`, `DELETE /{nota}/equipos/{linea}` |
| Contenido de un equipo desplegable (Código / Descripción / Lote / Vence / Estándar / Alistada / Consumo / Vr. unitario / Total), con las diferencias resaltadas y el precio general marcado | `GET /{nota}/equipos/{linea}/contenido`, `POST|PATCH|DELETE …/items` (con motivo) |
| Transiciones | `POST /{nota}/iniciar-alistamiento`, `/marcar-alistada`, `/despachar`, `/confirmar-entrega`, `/anular` |
| **Instrumentador (móvil)**: mis notas ENTREGADAS → equipos → consumibles; campo de escaneo (un lector tipo teclado o la cámara con `BarcodeDetector` y GS1), foto, confirmación del paciente y la fecha, firma en canvas | `GET /instrumentador/notas`, `GET /instrumentador/notas/{nota}`, `POST /instrumentador/notas/{nota}/consumo` |
| Devolución y conciliación | `GET /{nota}/devolucion`, `POST /{nota}/devolucion`, `POST /{nota}/cerrar` |
| Facturación: consumos pendientes, marcar facturado con número, anular con motivo | `/facturacion/consumos…` |
| Kardex por producto y lote | `/kardex/productos/{producto}`, `/kardex/lotes/{lote}` |
| Plantillas por tipo de cirugía | `/configuracion/tipos-cirugia…` |
| Composición permanente del equipo (pantalla mínima, auditada) | `/equipos/{equipo}/composicion` |

**Modo offline**: recomiendo dejarlo para después. Desde ahora el envío lleva `cliente_uuid` (idempotente), así que
agregar después un borrador local en IndexedDB con sincronización no exige cambiar el backend.

---

## 6. Pruebas

Infraestructura (requiere tu autorización, ver la sección 0.3‑1):
- **Opción recomendada**: correr las pruebas sobre **MySQL 8** (en Docker o en CI). `lockForUpdate`, las columnas `storedAs` y las sentencias
  `MODIFY` son de MySQL, y la prueba de concurrencia solo tiene sentido allí.
- Además hay que volver idempotentes las 2 migraciones rotas (`if (!Schema::hasColumn…)` y quitar el FK duplicado). En producción
  no tiene efecto porque ya están aplicadas, pero toca archivos existentes.

Pruebas de Feature y Unit previstas:
1. Sugerencia por tipo de cirugía: solo devuelve equipos disponibles, marca los ocupados (nota e institución) y ofrece el equivalente; no agrega nada sin confirmación.
2. Despliegue del contenido: cantidad estándar, alistada, lote y vencimiento, precio de la institución y precio general marcado, totales.
3. Modificación por nota: exige motivo, registra en la bitácora el valor anterior y el nuevo, no toca `equipo_detalles`, y se bloquea después de DESPACHADA.
4. FEFO y validaciones de lote: rechaza vencidos o en cuarentena, alerta los próximos a vencer, no permite superar el stock.
5. Reserva y despacho concurrentes: dos procesos sobre el mismo lote → solo uno gana (MySQL); un doble "Despachar" → un solo movimiento.
6. Registro de consumo: no supera lo entregado, lo ve solo el instrumentador asignado, queda en solo lectura después del envío, y un `cliente_uuid` repetido no duplica.
7. Facturación: precio congelado (cambiar después la lista de precios no altera el registro); un registro FACTURADO no se edita ni se borra; la anulación exige permiso.
8. Devolución por lote: vuelve al mismo lote y a la bodega origen; lo dañado va a cuarentena; se valida la ecuación de cierre.
9. Permisos por rol: matriz de 403/200 por acción.
10. Anulación de una nota despachada: reversa completa y saldos iguales a los iniciales.

---

## 7. Fases propuestas (con aprobación entre cada una)

| Fase | Contenido |
|---|---|
| **1. Fundamentos** | Infraestructura de pruebas, enums (estados y tipo de ítem), modelos de nota/detalle/consumo/movimiento, `bitacora`, permisos, roles y Policies, `MotorInventario` y `movimiento_inventario_detalles` |
| **2. Borrador y sugerencias** | tipos_cirugia y plantillas, medicos, pacientes, CRUD del borrador, `SugeridorEquipos` |
| **3. Alistamiento** | Contenido del equipo, precios, FEFO, modificaciones con motivo, reservas |
| **4. Despacho y entrega** | Consignación, despacho idempotente, confirmación de entrega, kardex |
| **5. Consumo** | Pantalla móvil, escaneo, adjuntos, firma, `consumos_facturables` |
| **6. Devolución, cierre, anulación y facturación** | Recepciones, cuarentena y baja, reversas, pantalla de facturación |
| Posterior | Modo offline, exportación a Contífico, reposiciones automáticas, carga de saldos iniciales en stock_lotes |

## 8. Decisiones que necesito de ti

1. **D1**: ¿los implantes viven dentro de cada equipo (equipo_inventario) o los equipos se arman desde la bodega en cada despacho?
2. **D2**: ¿`codigo_referencia` agrupa las copias N.1, N.2… del mismo equipo?
3. ¿De dónde salen hoy los precios por institución (Contífico, un Excel como `ortomax_precios`, u otro)? ¿Hay alquiler de motores pactado por institución?
4. ¿Autorizas correr las pruebas sobre MySQL y hacer idempotentes las 2 migraciones rotas?
5. ¿Apruebas los estados unificados de la sección 1.1?
