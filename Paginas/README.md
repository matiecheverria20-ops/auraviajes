# Sistema Web para Agencia de Viajes Minorista

**Proyecto académico — EPET N.º 7 “Vicente Arcadio Salemi”**  
**Curso:** 7.º IV — Programación

## 1. Contexto del proyecto

El objetivo es completar el desarrollo de **dos aplicaciones web relacionadas entre sí**, destinadas a una agencia de viajes minorista:

1. **Sistema Web Central (Back Office):** aplicación privada para que el personal de la agencia gestione clientes, reservas, viajes, proveedores, pagos, contenidos y tareas operativas.
2. **Landing Page (Front Office):** sitio público para presentar la agencia, mostrar destinos y paquetes, publicar contenido y recibir consultas o solicitudes de cotización.

Ambas aplicaciones deben trabajar con una **base de datos compartida**, de modo que la información pública relevante (paquetes, destinos, promociones y consultas) pueda administrarse desde el sistema central sin duplicar registros.

Este README funciona como documento de contexto para cualquier desarrollador o agente de IA que continúe el proyecto. Antes de modificar código, se debe revisar la estructura real del repositorio y el archivo SQL.

## 2. Objetivo general

Construir una solución web integrada que permita administrar el funcionamiento comercial y operativo de una agencia de viajes minorista, desde la captación de potenciales clientes hasta la gestión de reservas, cobros, documentación y seguimiento de los viajes.

## 3. Aplicaciones que componen el sistema

### 3.1. Sistema Web Central — Back Office

Aplicación de acceso privado destinada a administradores, gerentes, agentes y demás empleados autorizados.

Módulos funcionales esperados:

| Módulo | Responsabilidades principales |
|---|---|
| Panel principal | Resumen de ventas, reservas, cobros, vencimientos, próximos viajes y actividad reciente. |
| Clientes (CRM) | Alta, consulta, edición y seguimiento de clientes; datos personales, contacto, direcciones, documentos, preferencias, contactos de emergencia e historial. |
| Pasajeros asociados | Gestión de las personas que viajan en una reserva, aunque no sean titulares de la cuenta. Permitir asociar pasajeros a clientes o reservas según el modelo existente. |
| Reservas y expedientes | Crear y administrar operaciones de viaje, asociar cliente, pasajeros, productos, fechas, importes, agente responsable y estado. |
| Cotizaciones | Registrar solicitudes, preparar propuestas, calcular costos, descuentos, recargos, comisiones y precio final. Mantener trazabilidad entre cotización y reserva cuando corresponda. |
| Paquetes y salidas | Administrar paquetes, destinos, itinerarios, servicios incluidos, precios, cupos, fechas y disponibilidad. |
| Viajes y transporte | Gestionar viajes, rutas, vehículos, asientos, choferes, paradas y estados operativos cuando aplique. |
| Hoteles y servicios | Administrar hoteles, habitaciones, tipos de habitación, servicios, pensiones, traslados y seguros. |
| Proveedores | Mantener proveedores, tipos, contactos y servicios ofrecidos. |
| Pagos y cobranzas | Registrar pagos parciales o totales, medios de pago, moneda, fecha, número de operación y estado. Consultar importes abonados y saldos pendientes. |
| Caja y finanzas | Ofrecer una vista de ingresos y movimientos registrados. Las funciones de caja diaria, cuentas por pagar y flujo proyectado requieren verificar si el esquema actual las soporta. |
| Documentación y alertas | Consultar vencimientos de documentos, pagos pendientes, reservas próximas y notificaciones relevantes. |
| Empleados y permisos | Administrar empleados, roles, permisos, sucursales y acceso a las funciones del sistema. |
| Contenido público | Administrar banners, secciones de landing, paquetes, destinos, promociones y preguntas frecuentes. |
| Reportes | Generar indicadores comerciales, financieros y operativos utilizando datos reales de la base. |
| Auditoría | Consultar operaciones importantes realizadas por los usuarios, cuando estén registradas. |

### 3.2. Landing Page — Front Office

Sitio público orientado a potenciales viajeros. Debe ser responsive y priorizar claridad, confianza y facilidad de contacto.

Secciones esperadas:

- **Inicio:** presentación de la agencia, propuesta de valor y llamado a la acción.
- **Destinos:** listado de destinos destacados con imágenes e información.
- **Paquetes y ofertas:** paquetes publicados, precios orientativos, duración, descripción, destinos y promociones vigentes.
- **Detalle de paquete:** información ampliada, itinerario, servicios incluidos y formulario o enlace para consultar.
- **Cotización:** formulario para indicar destino, fechas, cantidad de viajeros, presupuesto aproximado, preferencias y datos de contacto.
- **Blog o novedades:** contenido inspiracional y práctico sobre viajes. Si el repositorio o la base no cuentan con un módulo de artículos, documentar la necesidad antes de crear tablas nuevas.
- **Contacto:** teléfono, correo, ubicación, redes y formulario de consultas.
- **Preguntas frecuentes:** respuestas administrables desde el Back Office si el modelo existente lo permite.

La Landing Page no debe permitir operaciones administrativas ni exponer datos privados. Los formularios públicos deben validar entradas y almacenar únicamente la información necesaria.

## 4. Base de datos existente

El proyecto cuenta con el archivo **`sistema_viajes.sql`**. El volcado corresponde a la base `sistema_viajes3` y fue generado con phpMyAdmin. El encabezado informa MariaDB 10.4.32 y PHP 8.0.30. El esquema contiene **97 tablas**.

### 4.1. Grupos de tablas identificados

La siguiente clasificación es orientativa y se basa en los nombres y relaciones del SQL. Antes de implementar una función, el agente debe revisar las columnas, claves primarias, claves foráneas, índices y restricciones de las tablas involucradas.

**Clientes, pasajeros y cuentas**
- `clientes`, `pasajero`, `pasajeroonline`
- `cliente_direccion`, `direcciones`, `contactos_emergencia`
- `documentos_cliente`, `tipos_documento`, `nacionalidades`
- `preferencias_cliente`, `favoritos`

**Reservas, viajes y transporte**
- `reservas`, `reserva_detalle`, `reserva_pasajero`, `reserva_seguro`
- `estados_reserva`, `historial_estados`, `cancelaciones`, `motivos_cancelacion`
- `viajes`, `salidas`, `pasaje`, `asiento`
- `rutas`, `paradas`, `destino`, `vehiculo`, `choferes`, `viaje_chofer`
- `mediotransporte`, `clases`, `cupos_salida`

**Paquetes, destinos y servicios turísticos**
- `paquetes`, `paquete_categoria`, `categorias_paquete`
- `paquete_destino`, `paquete_itinerario`, `paquete_servicio`
- `destino_categoria`, `categorias_destino`, `destino_informacion`
- `hoteles`, `habitaciones`, `tipohabitacion`, `hotel_servicios`
- `servicios`, `seguros`, `traslados`, `pension`
- `eventos`, `detalle_evento`, `evento_actividad`, `actividades`, `restricciones`

**Proveedores**
- `proveedores`, `tipos_proveedor`, `proveedor_tipo`
- `proveedor_contacto`, `proveedor_servicio`

**Pagos y datos comerciales**
- `pago`, `pagos_reserva`, `metodopago`, `monedas`
- `cuentas_corrientes`, `precios`, `precio_impuesto`, `impuestos`
- `promociones`, `promocion_paquete`, `cupones`, `cupon_uso`

**Usuarios, seguridad y operación**
- `empleados`, `establecimiento`, `empleado_sucursal`
- `roles`, `permisos`, `rol_permiso`, `usuario_rol`
- `sesiones`, `recuperacion_cuenta`, `codigos_verificacion`
- `auditoria`, `notificaciones`, `preferencias_notificacion`
- `dispositivos`, `tokens_push`

**Contenido público**
- `banners`, `secciones_landing`, `formularios_contacto`, `preguntas_frecuentes`

**Información complementaria**
- `h_viajes`, `valoracion`, `valoracionh`, `infoviajes`
- `paises`, `provincias`, `localidades`

### 4.2. Criterios obligatorios para trabajar con la base

1. **No recrear ni reemplazar la base de datos por una estructura inventada.**
2. Leer `sistema_viajes.sql` antes de crear consultas o modificar módulos.
3. Respetar los nombres exactos de tablas y columnas existentes, incluyendo mayúsculas y guiones bajos.
4. Usar las claves foráneas existentes para construir consultas y relaciones.
5. No asumir que dos tablas con nombres parecidos son equivalentes. Por ejemplo, `pago` y `pagos_reserva` deben analizarse por separado.
6. No eliminar columnas, tablas, registros ni restricciones para resolver errores de interfaz.
7. Si una funcionalidad requerida no está representada en el esquema, informar la brecha y proponer una migración SQL pequeña y justificada. No modificar el esquema sin explicar el motivo.
8. Toda migración debe ser compatible con MariaDB 10.4 y conservar los datos existentes.
9. Utilizar consultas preparadas y transacciones cuando una operación afecte varios registros relacionados.
10. Los datos de ejemplo del volcado son datos de desarrollo. No deben tratarse como información real ni como credenciales válidas.

### 4.3. Brechas que deben verificarse antes de implementar

Los requisitos académicos son más amplios que lo que puede confirmarse únicamente por los nombres de las tablas. Revisar especialmente:

- **Cotizaciones y expedientes:** determinar si `reservas` y `reserva_detalle` alcanzan para representar una cotización que todavía no fue confirmada. Si no, proponer una entidad específica y su relación con la reserva.
- **Facturación:** verificar si existe una estructura para facturas, recibos, numeración fiscal y comprobantes. No asumir que un registro de pago equivale a una factura.
- **Cuentas por pagar a proveedores:** verificar si hay tablas para obligaciones, vencimientos y pagos a proveedores. `cuentas_corrientes` está relacionada con clientes según la clave foránea visible; no asumir que cubre proveedores.
- **Caja diaria:** verificar si existe apertura, cierre y detalle de caja. No inferirlo de los pagos.
- **Comisiones y markups:** comprobar si hay reglas configurables por agente, proveedor, producto o cliente. No codificar porcentajes arbitrarios.
- **Familias y viajeros asociados:** analizar `reserva_pasajero` y `pasajero`; comprobar si existe una relación permanente de grupo familiar o solo asociación por reserva.
- **Preferencias completas del viajero:** revisar `preferencias_cliente` y `documentos_cliente`. No asumir que ya están modeladas todas las preferencias de aerolínea, hotel, asiento, alimentación, visados o programas de pasajero frecuente.
- **Blog:** verificar si existe una tabla de artículos antes de construir el módulo.
- **Integraciones externas:** no afirmar que hay conexión con GDS, bancos de camas, pasarelas de pago o proveedores si no existe una integración real configurada. Para el alcance académico, se puede comenzar con carga manual y dejar la integración documentada como futura mejora.

## 5. Tecnología y entorno

### Confirmado por el material disponible
- Base de datos: MariaDB 10.4.32 según el encabezado del volcado.
- Archivo de base de datos exportado desde phpMyAdmin.
- El encabezado del volcado informa PHP 8.0.30.

### Suposición inicial de trabajo
Si el repositorio actual no indica otra cosa, mantener el entorno PHP + MariaDB/MySQL que ya utiliza el proyecto (por ejemplo, XAMPP en desarrollo local). **No migrar de lenguaje, framework o arquitectura por iniciativa propia.**

Antes de comenzar:
- Inspeccionar los archivos del repositorio y detectar el stack realmente utilizado.
- Revisar rutas, estructura de carpetas, conexión a base de datos, autenticación y componentes existentes.
- Reutilizar las dependencias instaladas y el estilo de código del proyecto.
- Si se considera necesario incorporar un framework o dependencia nueva, explicar el beneficio y esperar autorización.

## 6. Requisitos de calidad y seguridad

- Interfaz responsive para escritorio, tablet y móvil.
- Diseño consistente entre las dos aplicaciones, con identidad visual compartida.
- Navegación clara, formularios comprensibles y estados de carga, éxito y error.
- Validación tanto en el navegador como en el servidor.
- Consultas SQL preparadas; nunca concatenar datos ingresados por usuarios en consultas.
- Contraseñas almacenadas con hash seguro mediante las funciones apropiadas de PHP (`password_hash` y `password_verify`).
- Control de acceso del lado del servidor en cada módulo; ocultar botones no reemplaza la autorización.
- Verificar rol y permisos antes de ejecutar operaciones sensibles.
- Escapar datos al mostrarlos en HTML para reducir riesgos de XSS.
- Protección CSRF en formularios que modifican información.
- No exponer contraseñas, hashes, tokens, documentos personales ni información privada en logs o respuestas públicas.
- No almacenar datos completos de tarjetas bancarias.
- Manejar errores sin mostrar rutas internas, consultas SQL ni credenciales.
- Usar transacciones para operaciones compuestas y evitar estados inconsistentes.
- Confirmar antes de cancelar reservas, anular pagos o eliminar información.
- Registrar en auditoría las acciones relevantes cuando corresponda.

## 7. Reglas de negocio generales

- Una reserva debe estar asociada a un cliente y puede incluir uno o varios pasajeros.
- Una reserva puede contener uno o varios detalles, según el diseño de `reserva_detalle`.
- Los estados de reserva deben respetar los registros de `estados_reserva`; no inventar valores incompatibles.
- Los cambios de estado importantes deben conservar historial cuando el modelo lo permita.
- Los pagos deben vincularse a la reserva correcta y conservar importe, moneda, método, fecha, referencia y estado.
- El saldo pendiente debe calcularse a partir del importe total y los pagos válidos, evitando contar pagos rechazados o anulados.
- Los cupos y asientos no deben asignarse por encima de la disponibilidad.
- Los paquetes que se muestran públicamente deben respetar su estado de publicación y las fechas de promociones.
- Los formularios públicos deben generar registros que luego puedan ser gestionados por un empleado.
- Las fechas, importes y estados deben mostrarse de manera consistente en ambas aplicaciones.
- Los importes deben manejarse con tipos decimales en base de datos; evitar cálculos monetarios con números de punto flotante sin control.

## 8. Reportes requeridos

Implementar reportes progresivamente, solo cuando las relaciones y datos disponibles permitan resultados confiables.

### Comerciales
- Ventas por agente, destino y tipo de producto.
- Margen bruto por expediente, agente y período, si existen costos y comisiones suficientes.
- Clientes ordenados por facturación o cantidad de viajes.

### Financieros
- Cuentas por cobrar y antigüedad de saldos.
- Cuentas por pagar a proveedores y vencimientos, si se incorpora o confirma el modelo correspondiente.
- Flujo de caja proyectado, cuando existan vencimientos y movimientos suficientes.
- Rentabilidad por proveedor o producto, si se registran costos y precios de venta.
- Ingresos por forma de pago.

### Operativos
- Reservas agrupadas por estado.
- Conversión de cotizaciones a reservas, si se registra el ciclo de cotización.
- Próximos viajes.
- Alertas por pagos o documentación próximos a vencer.

### Proveedores y empleados
- Volumen de compras por proveedor.
- Comisiones obtenidas por proveedor, si están registradas.
- Comisiones por agente, si existen reglas y datos de cálculo.
- Productividad por agente: expedientes gestionados y cerrados.

Los reportes deben usar consultas reales a la base de datos. No generar cifras ficticias para completar gráficos.

## 9. Plan de desarrollo recomendado

Trabajar en etapas pequeñas y verificables:

1. **Auditoría inicial:** inspeccionar el repositorio, SQL, rutas, conexión, autenticación y módulos ya desarrollados. Entregar un diagnóstico sin modificar archivos.
2. **Mapa de funcionalidades:** relacionar cada requisito con las tablas existentes, identificando lo implementado, pendiente y no soportado por la base.
3. **Base visual compartida:** definir estilos, navegación, componentes, responsive y estructura general de cada aplicación.
4. **Seguridad y usuarios:** validar inicio de sesión, sesiones, roles y permisos.
5. **Back Office básico:** panel, clientes, empleados, proveedores, destinos y paquetes.
6. **Landing Page:** inicio, destinos, paquetes, ofertas, contacto y formulario de cotización.
7. **Operaciones:** reservas, pasajeros, detalles, salidas, viajes, estados e historial.
8. **Pagos:** registro de pagos, saldos, métodos y reportes disponibles.
9. **Itinerarios y alertas:** generar información para el viajero y avisos operativos.
10. **Reportes:** incorporar indicadores después de validar las reglas de cálculo.
11. **Pruebas integrales:** probar flujos completos desde la consulta pública hasta la gestión interna.
12. **Documentación final:** explicar instalación, configuración, usuarios de prueba y funcionamiento.

No intentar implementar todos los módulos en una sola modificación. Cada etapa debe dejar el proyecto ejecutable.

## 10. Instrucciones para agentes de IA (OpenCode u otros)

Este documento y `sistema_viajes.sql` son contexto del proyecto, no autorización para hacer cambios destructivos.

### Antes de editar
1. Leer este README completo.
2. Inspeccionar la estructura real del repositorio.
3. Leer las partes relevantes de `sistema_viajes.sql`.
4. Identificar qué existe y qué falta; no duplicar funcionalidades ya implementadas.
5. Resumir el plan de cambios y los archivos que se modificarán.

### Durante el desarrollo
- Trabajar sobre el código existente y respetar sus convenciones.
- Hacer cambios pequeños, coherentes y fáciles de revisar.
- No reemplazar archivos completos si basta con modificar una sección.
- No inventar nombres de columnas, relaciones, rutas, credenciales ni datos.
- No introducir datos ficticios en reportes o indicadores.
- No modificar el esquema SQL sin justificar la necesidad y presentar la migración.
- Mantener separadas las responsabilidades del Back Office y la Landing Page.
- La Landing Page solo debe consumir o mostrar datos públicos.
- Mantener la lógica sensible y las validaciones en el servidor.
- No eliminar funcionalidades existentes para simplificar el desarrollo.
- Si falta información, detenerse y preguntar en lugar de tomar decisiones estructurales irreversibles.

### Al terminar cada tarea
1. Enumerar archivos creados y modificados.
2. Explicar qué se implementó y cómo probarlo.
3. Informar cambios de base de datos, si los hubo.
4. Ejecutar o indicar pruebas relevantes y sus resultados reales.
5. Mencionar limitaciones o tareas pendientes.
6. No afirmar que algo funciona si no fue probado.

### Primera instrucción sugerida para OpenCode

> Lee `README.md` y analiza todo el proyecto junto con `sistema_viajes.sql`. Por ahora no modifiques ningún archivo. Identifica el stack tecnológico real, la estructura de carpetas, las funcionalidades ya implementadas, las tablas y relaciones relevantes, y las diferencias entre los requisitos y el estado actual. Devuelve un diagnóstico organizado por: Back Office, Landing Page, base de datos, seguridad, funcionalidades pendientes y riesgos técnicos. No inventes información ni propongas reemplazar la base existente.

## 11. Criterio de finalización

El proyecto se considerará integrado cuando:

- El personal pueda ingresar al Back Office con permisos según su rol.
- El personal pueda gestionar clientes, paquetes, destinos, proveedores, reservas y pagos contemplados por el alcance.
- La Landing Page muestre información pública administrada desde el sistema central.
- Las consultas y solicitudes de cotización recibidas desde la web puedan ser atendidas desde el Back Office.
- Los datos compartidos se mantengan consistentes.
- Los reportes implementados se calculen con información real.
- Los flujos principales hayan sido probados y exista documentación de instalación y uso.

---

**Nota:** este documento describe el alcance funcional solicitado y el esquema SQL entregado. No afirma que todos los módulos ya estén desarrollados ni que todas las funciones requeridas estén actualmente representadas en la base de datos.
