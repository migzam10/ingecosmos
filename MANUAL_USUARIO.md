# INGECOSMOS — Manual de Usuario
## Sistema de Gestión de Taller Automotriz
### Versión 1.0 · Junio 2026

---

## ¿Qué es este sistema?

INGECOSMOS es el sistema de gestión digital del taller. Reemplaza el Excel de control y permite que cada persona del taller (recepción, cotizador, coordinador, técnicos) trabaje desde su propio usuario, vea solo lo que le corresponde y registre en tiempo real el avance de cada vehículo.

El sistema maneja tres tipos de clientes:
- **Aseguradoras** (SURA, BOLIVAR, COLPATRIA, ZURICH, etc.) — proceso completo con cotización, autorización y repuestos
- **Flotas** (DHL, FRITOLAY, UNINORTE, etc.) — igual que aseguradoras pero aprobación más rápida
- **Particulares** (PERSONAL) — proceso simplificado sin autorización ni repuestos formales

---

## Roles del sistema — quién puede hacer qué

| Rol | Descripción |
|---|---|
| **ADMIN** | Acceso total. Administra usuarios, técnicos, empresas y todo el sistema |
| **COORDINADOR** | Opera el taller: avanza estados de OTs, asigna técnicos, gestiona entregas y liquidaciones |
| **COTIZADOR** | Elabora cotizaciones. Ve las OTs y la torre de control pero no puede cambiar estados |
| **RECEPCION** | Crea OTs, registra el inventario del vehículo y sube fotos de ingreso |
| **TECNICO** | Ve solo sus tareas asignadas: inicia, comenta y finaliza su propio trabajo |

> Un usuario puede tener más de un rol. Por ejemplo, el administrador tiene ADMIN + COORDINADOR.

---

## Módulo 1 — Panel de Control (Dashboard)

**Quién lo ve:** Todos los usuarios

**Qué muestra:**

El dashboard cambia según el rol:

- **Técnicos:** ven sus tareas activas (en proceso y pendientes), cuántos trabajos han finalizado en el mes y su saldo de liquidación.

- **Todos los demás:** ven los indicadores globales del taller en tiempo real:
  - Órdenes activas en este momento
  - Cuántas están vencidas (semáforo INCUMPLIDO)
  - Cuántas se deben entregar hoy
  - Cuántas están sin cotizar
  - Cuántas esperan autorización de la CIA
  - **Panel de alertas** con las situaciones que requieren atención inmediata, clasificadas por urgencia

**Accesos rápidos:** botones directos a los módulos más usados según el rol.

**Observación de proceso encontrada:**
Las alertas del dashboard le muestran al COTIZADOR situaciones que no puede resolver (entregas vencidas, repuestos demorados). Solo debería ver las alertas de "Sin cotizar" y "Sin autorización". Este ajuste está pendiente de implementar.

---

## Módulo 2 — Órdenes de Trabajo (OT)

**Quién lo ve:** ADMIN, COORDINADOR, RECEPCION, COTIZADOR

**Qué es una OT:**
Cada vehículo que entra al taller recibe un número de Orden de Trabajo único (continúa desde el Excel histórico, actualmente en el rango 49.600+). Ese número identifica el carro durante toda su estadía.

### Crear una OT (RECEPCION / COORDINADOR / ADMIN)

Al crear una OT se registra:
- **Datos del vehículo:** placa, marca, modelo, color, año
- **Datos del cliente:** nombre, cédula, teléfono, correo, dirección
- **Empresa / CIA:** quién cubre la reparación (SURA, BOLIVAR, PERSONAL, etc.)
- **Área del taller:** Latonería y Pintura (LYP) o Mecánica
- **Información de ingreso:** kilometraje, nivel de combustible, si ingresó en grúa, documentos y llaves entregadas
- **Número FORC:** número del caso asignado por la aseguradora (solo para aseguradoras)
- **Inventario B/R/G:** estado de cada parte del vehículo (Bueno / Regular / Malo)
- **Fotos:** registro fotográfico del estado del vehículo al ingresar

### Vista de lista de OTs

Tiene dos modos:
- **Activas:** muestra solo los vehículos que están actualmente en el taller
- **Historial completo:** muestra todas las OTs incluidas las ya entregadas

Cuando una OT entregada fue tardía (se entregó después de la fecha prometida), aparece una etiqueta roja **"Tardío · Xd"** al lado del estado, indicando cuántos días se pasó.

### Vista de detalle de una OT

Muestra toda la información del vehículo organizada en secciones:
1. **Encabezado de estado:** semáforo, estado actual, fecha de entrega estimada, días faltantes. Si ya fue entregada, indica si fue **Oportuna** o **Tardía** y por cuántos días.
2. **Panel de avance de estado** (solo COORDINADOR/ADMIN): formularios para avanzar al siguiente estado
3. **Datos del vehículo y cliente**
4. **Inventario B/R/G**
5. **Fotos**
6. **Técnicos asignados** y su actividad (comentarios y fotos de avance)
7. **Entregas parciales** (cuando el cliente recoge el carro temporalmente)
8. **Cotizaciones** vinculadas
9. **Historial completo** de cambios de estado

---

## Módulo 3 — Estados de una OT y el Semáforo

Este es el corazón del sistema. Cada OT avanza por una serie de estados que reflejan en qué punto del proceso está el vehículo.

### Flujo normal (Aseguradoras / Flotas)

```
PENDIENTE COTIZACIÓN
    ↓
PENDIENTE AUTORIZACIÓN (CIA)
    ↓
PENDIENTE ORDEN REPUESTOS
    ↓
ESPERANDO REPUESTOS
    ↓
REPUESTOS INSTALADOS
    ↓
EN PROCESO (reparación activa)
    ↓
PROGRAMADO PARA ENTREGA
    ↓
ENTREGADO ✓
```

### Flujo simplificado (Particulares)

```
PENDIENTE COTIZACIÓN → EN PROCESO → ENTREGADO
```

### Estados especiales (pueden activarse desde cualquier punto)

| Estado | Cuándo se usa |
|---|---|
| NO AUTORIZADO | La aseguradora rechazó la cotización |
| ORDEN ANULADA | El cliente cancela la reparación |
| PÉRDIDA TOTAL | El vehículo es declarado pérdida total por la CIA |
| VFT | Vehículo Fuera del Taller — el cliente lo retira sin terminar |
| GARANTÍA | El vehículo regresa por garantía de una reparación anterior |
| ARREGLO DIRECTO | El cliente arregla directamente con el proveedor |
| ENTREGA PARCIAL | El cliente recoge el vehículo mientras continúa la reparación |

### El Semáforo

El semáforo es el indicador más importante del sistema. Se recalcula automáticamente cada vez que hay un cambio y se basa en la **fecha de entrega estimada** (salida estimada).

| Semáforo | Significado | Cuándo aparece |
|---|---|---|
| 🔵 **SIN FECHA** | No hay fecha de entrega calculada | El proceso aún no ha iniciado (sin cotización o sin fecha de inicio) |
| 🟢 **A TIEMPO** | El vehículo está dentro del plazo | La fecha estimada es posterior a hoy |
| 🟡 **ENTREGAR HOY** | Debe entregarse hoy | La fecha estimada es exactamente hoy |
| 🔴 **INCUMPLIDO** | La fecha prometida ya venció | La fecha estimada ya pasó y el carro sigue en el taller |
| ✅ **OK** | OT cerrada | El vehículo fue entregado o la OT fue cerrada por otro motivo |

**¿Cómo se calcula la fecha de entrega estimada?**

La fecha no es fija — la calcula el sistema automáticamente cuando el coordinador registra el inicio del proceso, usando esta fórmula:

```
Valor MO de la cotización ÷ Tarifa hora de la empresa = Horas Artesano (HA)
Horas Artesano ÷ 8 × 1.5  →  redondear hacia arriba = Días de Reparación (DR)
Fecha inicio proceso + DR días hábiles (sin sábados, domingos ni festivos) = Fecha estimada
```

El sistema también clasifica automáticamente el **Tamaño del Golpe (TG)**:
- **Leve:** hasta 5 días de reparación — meta CIA: 5 días
- **Medio:** 6 a 10 días — meta CIA: 10 días
- **Fuerte:** más de 10 días — meta CIA: 13 días

**Observación de proceso encontrada:**
Una OT puede quedar en SIN FECHA incluso después de tener cotización, si el coordinador no ha registrado la fecha de inicio del proceso. Es importante que el coordinador registre esta fecha el mismo día que entra a reparación, para que el semáforo funcione correctamente.

---

## Módulo 4 — Torre de Control

**Quién lo ve:** ADMIN, COORDINADOR, COTIZADOR

**Qué es:**
La torre es la vista central de operación del taller. Muestra todas las OTs activas (los vehículos que están en este momento en el taller) con su semáforo, estado, fecha de entrega y técnicos asignados.

**Pestañas:** permite filtrar por semáforo — ver todas, solo incumplidas, entregar hoy, a tiempo o sin fecha.

**Filtros disponibles:** por área (LYP / Mecánica), empresa/CIA, estado específico, y técnico asignado.

**Ordenamiento:** las OTs incumplidas aparecen primero, luego entregar hoy, luego a tiempo, y al final sin fecha. Dentro de cada grupo, ordenadas por fecha de entrega.

**Observación de proceso encontrada:**
El COTIZADOR ve en la torre la columna de técnicos asignados y el filtro por técnico, información que no necesita para su trabajo. Este ajuste está pendiente.

---

## Módulo 5 — Cotizaciones

**Quién lo gestiona:** COTIZADOR, COORDINADOR, ADMIN

**Qué es:**
La cotización es el documento que detalla el valor de la reparación. Se envía a la aseguradora para que apruebe o rechace el trabajo.

### Cómo crear una cotización

Desde el detalle de la OT, el botón **"+ Nueva Cotización"** aparece cuando la OT está en PENDIENTE COTIZACIÓN o PENDIENTE AUTORIZACIÓN.

La cotización incluye:
- **Mano de Obra (MO):** ítems del trabajo físico de reparación. Se pueden seleccionar del catálogo o escribir libremente. El precio en la cotización puede ajustarse sin afectar el catálogo.
- **Repuestos (RTO):** valor de las piezas que se van a cambiar
- **Insumos de pintura:** materiales de pintura (el sistema aplica automáticamente el 25% de markup sobre el costo)
- **Terceros:** trabajos subcontratados a otro taller especializado
- **Otros gastos:** cualquier gasto adicional no clasificado

> **Importante:** Los clientes Tipo B (ZURICH, QUALITAS) ponen sus propios repuestos. Para estos clientes el valor de repuestos NO se incluye en el total de la cotización aunque se registre.

Al guardar la cotización, la OT avanza automáticamente a **PENDIENTE AUTORIZACIÓN**.

### Estados de una cotización

- **Borrador:** recién creada, se puede editar y eliminar
- **Autorizada:** la CIA aprobó
- **Rechazada:** la CIA no aprobó

**Observación de proceso encontrada:**
Con 519 OTs en el sistema y solo 10 cotizaciones registradas, es evidente que la mayoría de datos históricos del Excel se migraron sin cotizaciones. Esto hace que muchos registros históricos no tengan fecha de entrega calculada (SIN FECHA) y no aporten a los promedios de producción. A futuro, al migrar datos históricos conviene registrar al menos el valor de MO para que los cálculos funcionen.

---

## Módulo 6 — Mis Tareas (Panel del Técnico)

**Quién lo ve:** TECNICO, COORDINADOR, ADMIN

**Qué es:**
El módulo que usa el técnico día a día. Muestra únicamente las OTs que le fueron asignadas, con el detalle de qué trabajo debe hacer.

### Flujo del técnico

1. El coordinador asigna al técnico a una OT (desde el detalle de la OT) indicando la especialidad: Latonero, Preparador, Pintor, Mecánico, Electricista, Aire Acondicionado o Diagnóstico.

2. El técnico entra a **Mis Tareas** y ve su tarea en estado **Pendiente**.

3. El técnico hace clic en **Iniciar** cuando comienza a trabajar. La OT pasa automáticamente a EN PROCESO si aún no lo estaba.

4. Durante el trabajo, el técnico puede:
   - Agregar **comentarios** de avance
   - Subir **fotos** del proceso

5. Al terminar, el técnico hace clic en **Finalizar**.

6. **Automatismo crítico:** Cuando el ÚLTIMO técnico asignado a una OT finaliza su tarea, el sistema cambia automáticamente el estado de la OT a **PROGRAMADO PARA ENTREGA** y lo registra en el historial. El coordinador no tiene que hacer nada.

**Historial:** el técnico puede ver el historial de sus trabajos anteriores.

**Observación de proceso encontrada:**
Si el coordinador asigna a un técnico pero luego lo quita antes de que inicie, ese técnico nunca aparece en el historial. Si se reasigna después de que otro técnico ya finalizó, el automatismo de "todos finalizaron" puede no dispararse correctamente porque el conteo incluye al nuevo técnico que aún no empezó. Se recomienda no cambiar la asignación de técnicos una vez que alguno ya inició su trabajo.

---

## Módulo 7 — Liquidación de Técnicos

**Quién lo ve:** ADMIN, COORDINADOR

**Qué es:**
El módulo de pago a técnicos. Muestra cuánto generó cada técnico en el mes y qué se le ha pagado.

### Cómo funciona

**Paso 1 — Asignar valor por OT:**
Desde el detalle de cada OT, en la sección de técnicos asignados, el coordinador ingresa el **valor a liquidar** para cada técnico en esa OT. Este valor es independiente del valor de la cotización — es lo que el taller le paga al técnico por ese trabajo específico.

**Paso 2 — Ver el resumen mensual:**
En Liquidación → seleccionar mes y año → aparece la lista de todos los técnicos activos con:
- Total ganado en el mes (suma de valores asignados en OTs finalizadas)
- Total pagado (abonos y anticipos registrados)
- Saldo pendiente

**Paso 3 — Registrar pagos:**
Desde el detalle de cada técnico se pueden registrar pagos con tres tipos:
- **Anticipo:** pago antes de cerrar el mes
- **Abono:** pago parcial
- **Pago final:** cierre del mes

Cada pago genera un **recibo PDF** para entregar al técnico.

**Paso 4 — PDF de liquidación:**
Al final del mes se puede descargar el PDF completo de liquidación del técnico con el detalle de todas sus OTs y todos los pagos realizados.

**Observación de proceso encontrada:**
El sistema filtra los trabajos del mes por la fecha de creación del trabajo o por la fecha de inicio del proceso de la OT. Esto puede generar inconsistencias: si una OT inicia en mayo pero el trabajo del técnico se crea en junio, puede no aparecer en el mes correcto. Se recomienda asignar los técnicos el mismo día que se inicia el proceso de la OT.

---

## Módulo 8 — Catálogo de Mano de Obra

**Quién lo gestiona:** ADMIN, COORDINADOR

**Qué es:**
El catálogo es la lista de referencia de trabajos y sus precios. Cuando el cotizador abre una cotización, puede seleccionar ítems del catálogo en lugar de escribirlos a mano.

### Niveles del catálogo

El catálogo tiene tres niveles de especificidad:

| Nivel | Aplica a | Ejemplo |
|---|---|---|
| **1 - Genérico** | Todos los vehículos | "Pintura de puerta" |
| **2 - Por marca** | Solo esa marca | "Pintura de puerta RENAULT" |
| **3 - Por marca y modelo** | Solo ese modelo específico | "Pintura de puerta RENAULT Duster" |

Cuando se abre una cotización, el sistema muestra primero los ítems más específicos para el vehículo de la OT, luego los de su marca, y finalmente los genéricos.

> **Regla importante:** El precio del catálogo es solo de referencia. El cotizador puede modificar el precio en cada cotización sin afectar el catálogo base.

**Observación de proceso encontrada:**
El catálogo tiene actualmente 33 ítems activos para un taller con 519 OTs históricas. Esto indica que el catálogo está muy poco poblado. Un catálogo completo debería tener cientos de ítems por marca y modelo. Se recomienda ir agregando los trabajos más frecuentes para agilizar el proceso de cotización.

---

## Módulo 9 — Producción y KPIs

**Quién lo ve:** ADMIN, COORDINADOR

**Qué muestra:**
Análisis histórico del rendimiento del taller. Se puede filtrar por año, mes y área.

### Indicadores principales

| Indicador | Qué mide |
|---|---|
| **Total órdenes** | Cuántas OTs se recibieron en el período |
| **Entregadas** | Cuántas se completaron |
| **% Entregas oportunas** | Qué porcentaje se entregó antes o en la fecha prometida |
| **Ticket promedio** | Valor promedio por OT entregada |
| **TMP** | Tiempo total promedio: desde que ingresó el carro hasta que se entregó |
| **TMR** | Tiempo de reparación: desde que inició el proceso hasta que terminó |

### Indicadores de proceso (cuellos de botella)

| Indicador | Qué revela |
|---|---|
| **Días hasta cotización** | Cuánto tarda el cotizador en elaborar la cotización |
| **Días hasta autorización** | Cuánto tarda la CIA en responder |
| **Días llegada repuestos** | Cuánto tarda el proveedor en entregar |

Si los "días hasta autorización" son altos, el problema no está en el taller sino en la CIA. Esto es clave para negociar con las aseguradoras.

### Gráficas

- **OTs por mes:** barras que comparan cuántas ingresaron vs cuántas se entregaron. Si las barras de ingreso son siempre mayores, hay acumulación de trabajo.
- **Facturación mensual:** ingresos del mes (agrupados por fecha de ingreso de la OT, que corresponde al mes en que se realizó el trabajo).
- **Órdenes por empresa:** qué clientes generan más trabajo.

> **¿Por qué la facturación usa la fecha de ingreso y no la de entrega?** Porque contablemente, el trabajo se realiza y cobra en el mes en que el vehículo estuvo en el taller, no en el mes de entrega. Una OT de diciembre que se entrega en enero sigue siendo facturación de diciembre.

### Tabla Top 10 clientes

Muestra los 10 clientes que más facturación generaron en el período, con un botón para descargar el detalle mes a mes en Excel con tres hojas: todos los clientes, solo LYP, solo Mecánica.

---

## Módulo 10 — Administración (solo ADMIN)

**Quién lo ve:** ADMIN únicamente

Contiene cuatro secciones:

### Usuarios
Crear y gestionar los usuarios del sistema. Por cada usuario se define:
- Nombre, correo y contraseña
- Uno o más roles (ADMIN, COORDINADOR, COTIZADOR, RECEPCION, TECNICO)
- Si está activo o no

> El registro público está deshabilitado. Solo el administrador puede crear usuarios.

### Técnicos
Gestión del catálogo de técnicos del taller. Cada técnico tiene:
- Nombre
- Especialidades (puede tener varias: Latonero, Preparador, Pintor, Mecánico, Electricista, AA)
- Usuario del sistema vinculado (para que pueda iniciar sesión con su panel de técnico)

> Un técnico sin usuario vinculado puede ser asignado a OTs por el coordinador, pero no podrá iniciar sesión para ver Mis Tareas.

### Empresas / CIAs
Configuración de cada cliente empresarial:
- Nombre, tipo (A o B)
- Tarifa por hora de mano de obra (determina el cálculo de HA y DR)
- Metas de días por TG (Leve/Medio/Fuerte) — define qué se considera oportuno para esa CIA

### Días Festivos
Lista de festivos de Colombia precargada para 2025 y 2026. El sistema los usa al calcular la fecha de entrega estimada (no cuenta sábados, domingos ni festivos).

---

## Cómo están conectados todos los módulos

```
RECEPCIÓN crea OT
    │
    ├─→ COTIZADOR elabora cotización ─────────────────────────────────┐
    │                                                                  │
    │   COORDINADOR avanza estados:                                    │
    ├─→ Autoriza CIA → Pide repuestos → Recibe repuestos → Inicia proceso
    │           │                                          │
    │           │                                          ↓
    │           │                              TÉCNICOS trabajan en Mis Tareas
    │           │                                          │
    │           │                              Todos finalizan → Sistema pasa a
    │           │                              PROGRAMADO ENTREGA (automático)
    │           │                                          │
    │           └─→ COORDINADOR entrega vehículo ──────────┘
    │
    ├─→ PRODUCCIÓN/KPIs recoge toda la data para análisis histórico
    │
    ├─→ LIQUIDACIÓN usa los valores asignados por OT para pagar técnicos
    │
    └─→ ALERTAS monitorea en tiempo real y avisa en el DASHBOARD
```

---

## Sistema de Alertas — qué vigila automáticamente

El sistema revisa en cada carga del dashboard si hay situaciones que requieren atención:

| Alerta | Condición | Urgencia |
|---|---|---|
| 🔴 Entrega vencida | La fecha prometida ya pasó y el carro sigue en el taller | Urgente |
| 🟡 Entregar hoy | La fecha estimada es hoy | Aviso |
| 🟡 Sin cotizar | La OT lleva más de 2 días hábiles sin cotización | Aviso |
| 🟡 Sin autorización | Lleva más de 5 días esperando respuesta de la CIA | Aviso |
| 🟠 Autorizada sin iniciar | Fue autorizada hace más de 1 día pero no ha entrado a reparación | Atención |
| 🟠 Repuestos demorados | Llevan más de 10 días desde la autorización sin llegar | Atención |
| 🟠 Entrega parcial sin retorno | El cliente se llevó el carro hace más de 30 días y no ha retornado | Atención |

---

## Glosario de términos usados en el sistema

| Término | Significado |
|---|---|
| **OT** | Orden de Trabajo — número único de cada vehículo en el taller |
| **HA** | Horas Artesano — cuántas horas de trabajo tiene la OT (MO ÷ tarifa) |
| **DR** | Días Reales — días hábiles estimados de reparación física |
| **TG** | Tamaño del Golpe — Leve / Medio / Fuerte según el DR |
| **TMP** | Tiempo Total del Proceso — días desde ingreso hasta entrega al cliente |
| **TMR** | Tiempo de Reparación Real — días desde inicio proceso hasta terminación |
| **MO** | Mano de Obra |
| **RTO** | Repuesto |
| **FORC** | Número del caso asignado por la aseguradora al siniestro |
| **CIA** | Compañía de seguros / aseguradora |
| **LYP** | Latonería y Pintura (área del taller) |
| **VFT** | Vehículo Fuera del Taller |
| **Tipo A** | Cliente que paga MO + Repuestos + Insumos (mayoría) |
| **Tipo B** | Cliente que pone sus propios repuestos (ZURICH, QUALITAS) |
| **Oportuno** | OT entregada antes o en la fecha prometida |
| **Tardío** | OT entregada después de la fecha prometida |
| **B/R/G** | Bueno / Regular / Malo — estado del inventario del vehículo |

---

## Observaciones generales del proceso — puntos de mejora identificados

Durante el análisis del sistema se identificaron los siguientes puntos que conviene revisar o tener en cuenta:

1. **Registro del inicio del proceso:** Es fundamental que el coordinador registre la fecha de inicio el mismo día que el vehículo entra a reparación. Sin esta fecha, el semáforo no puede calcular la entrega estimada y la OT queda en SIN FECHA indefinidamente.

2. **Catálogo de MO incompleto:** Con 33 ítems activos el catálogo es muy básico. Completarlo gradualmente con los trabajos más frecuentes del taller agilizará el trabajo del cotizador y dará más consistencia a los precios.

3. **Datos históricos sin cotización:** La mayoría de OTs migradas del Excel no tienen cotización registrada. Esto afecta los promedios de producción y el % de oportunidad. No es urgente corregirlo, pero es bueno tenerlo en cuenta al interpretar los indicadores históricos.

4. **Asignación de técnicos:** Se recomienda asignar todos los técnicos al momento de iniciar el proceso, no ir agregándolos a medida que avanza la OT. Agregar un técnico cuando otros ya finalizaron puede afectar el automatismo de cambio a PROGRAMADO ENTREGA.

5. **Valor a liquidar por OT:** El coordinador debe ingresar el valor a liquidar de cada técnico en el detalle de la OT antes de cerrar el mes. Si este valor queda en $0, el técnico no tendrá saldo en la liquidación aunque haya trabajado.

6. **Entregas parciales:** Si un cliente recoge el vehículo temporalmente, es importante registrar el retorno cuando regresa. Las entregas parciales sin retorno por más de 30 días generan alerta naranja en el dashboard.

7. **Cotizador y datos de OT:** El cotizador puede ver todas las OTs pero no debería editar datos del vehículo o cliente. Esa función corresponde a Recepción o Coordinador. Ajuste de permisos pendiente.

---

*Documento generado el 2 de junio de 2026 — INGECOSMOS Sistema de Gestión de Taller*
