# INGECOSMOS — Sistema de Gestión de Taller Automotriz
## Archivo de memoria permanente para Claude Code
## Versión 1.0 — No modificar sin autorización del desarrollador principal

---

## CONTEXTO DEL NEGOCIO

Taller automotriz que digitaliza su proceso de gestión de órdenes de trabajo.
Opera con tres tipos de clientes: **Aseguradoras**, **Flotas** y **Particulares**.
Dos áreas físicas de trabajo: **LYP** (Latonería y Pintura) y **MECANICA**.

---

## STACK TECNOLÓGICO — OBLIGATORIO RESPETAR

| Componente | Versión | Notas |
|---|---|---|
| PHP | 8.2+ | Requerido por Laravel 11 |
| Laravel | 11.53.1 | Versión exacta del hosting |
| MySQL | 8.x | Base de datos en hosting cPanel |
| Frontend | Tabler (Bootstrap 5) | Via CDN — NO usar npm build |
| Gráficas | Chart.js | Via CDN |
| PDF | barryvdh/laravel-dompdf | Cotizaciones y liquidaciones |
| Auth | Laravel Breeze (session) | Sin JWT, sin tokens |
| Hosting | latinoamericahosting.com.co | cPanel, PHP 8.x, MySQL activo |
| Control versiones | Git + GitHub | Commit por fase completada |

**REGLA ABSOLUTA DE FRONTEND:**
- Tabler via CDN únicamente — no instalar npm, no compilar assets
- JS vanilla únicamente — no Vue, no React, no Alpine
- Un solo archivo CSS propio: `public/css/app.css`
- Un solo archivo JS propio: `public/js/app.js`
- Diseño **Mobile First** — primero celular/tablet, luego PC
- En móvil: cards apiladas, botones grandes, sin tablas horizontales
- En PC: sidebar fijo, tablas completas, layout de dos columnas

---

## ESTRUCTURA DE CARPETAS DEL PROYECTO

```
taller/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   ├── Admin/
│   │   │   ├── Tecnico/
│   │   │   └── [ModuloController].php
│   │   ├── Middleware/
│   │   │   └── CheckRole.php
│   │   └── Requests/
│   ├── Models/
│   ├── Policies/
│   ├── Services/          ← lógica de negocio aquí, NO en controllers
│   │   ├── OTService.php
│   │   ├── CotizacionService.php
│   │   └── LiquidacionService.php
│   └── Helpers/
│       └── TallerHelper.php
├── database/
│   ├── migrations/        ← una migration por tabla
│   ├── seeders/
│   │   ├── DatabaseSeeder.php
│   │   ├── EmpresasClienteSeeder.php
│   │   ├── TecnicosSeeder.php
│   │   ├── MarcasSeeder.php
│   │   ├── FestivosSeeder.php
│   │   └── CatalogoMOSeeder.php
│   └── factories/
├── resources/
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php      ← layout principal con sidebar
│       │   └── auth.blade.php     ← layout solo para login
│       ├── components/
│       │   ├── semaforo.blade.php
│       │   ├── estado-badge.blade.php
│       │   ├── tg-badge.blade.php
│       │   └── kpi-card.blade.php
│       ├── auth/
│       ├── dashboard/
│       ├── ordenes/
│       ├── torre/
│       ├── cotizaciones/
│       ├── tecnicos/
│       ├── mis-tareas/
│       ├── liquidacion/
│       ├── produccion/
│       ├── catalogo/
│       ├── entregas-parciales/
│       └── admin/
├── routes/
│   ├── web.php
│   └── api.php            ← endpoints AJAX
├── public/
│   ├── css/
│   │   └── app.css        ← estilos propios únicamente
│   └── js/
│       └── app.js         ← JS propio únicamente
└── storage/
    └── app/
        └── public/
            └── fotos/     ← fotos de OTs
```

---

## CONVENCIONES DE CÓDIGO — OBLIGATORIO RESPETAR

### PHP / Laravel
- Nombres de modelos: `PascalCase` singular → `OrdenTrabajo`, `EmpresaCliente`
- Nombres de tablas: `snake_case` plural → `ordenes_trabajo`, `empresas_cliente`
- Nombres de controllers: `PascalCase` + `Controller` → `OrdenTrabajoController`
- Lógica de negocio compleja: va en `Services/`, no en controllers
- Controllers: solo reciben request, llaman al service, devuelven vista o JSON
- Validaciones: siempre en `FormRequest` classes, nunca inline en controllers
- Eloquent: usar relaciones definidas, nunca queries SQL raw salvo casos justificados
- Comentarios en español para lógica de negocio específica del taller

### Blade / Frontend
- Componentes reutilizables en `resources/views/components/`
- Variables en vistas: siempre escapar con `{{ }}`, nunca `{!! !!}` salvo HTML confiable
- Formularios: siempre con `@csrf`
- Confirmaciones destructivas: siempre con `confirm()` en JS antes de submit
- Mensajes flash: session `success`, `error`, `info`, `warning`

### Base de datos
- Migrations: una por tabla, nombradas `create_[tabla]_table`
- Seeders: datos reales del taller (clientes, técnicos, marcas, festivos)
- Foreign keys: siempre con `constrained()` y `onDelete` explícito
- Timestamps: `created_at` y `updated_at` en todas las tablas
- Soft deletes: NO usar — el taller prefiere registros de auditoría

### Git
- Un commit por fase completada: `feat: fase-1 autenticacion y layout base`
- Commits intermedios para cambios importantes: `feat: modelo OrdenTrabajo con lógica WORKDAY`
- Nunca commitear `.env` — está en `.gitignore`
- Branch principal: `main`

---

## GLOSARIO OFICIAL DEL SISTEMA

Usar estos términos exactos en código, comentarios, vistas y mensajes al usuario:

| Sigla | Significado completo |
|---|---|
| **OT** | Orden de Trabajo — número único que identifica cada carro |
| **DT** | Días Transcurridos en taller desde el ingreso |
| **DR** | Días Reales estimados de reparación |
| **HA** | Horas Artesano = Total OT / tarifa hora del cliente |
| **TG** | Tamaño del Golpe: Leve / Medio / Fuerte |
| **TMP** | Tiempo Total del Proceso (ingreso → entrega cliente) |
| **TMR** | Tiempo de Reparación Real (inicio proceso → terminación) |
| **D_FAL** | Días Faltantes para la fecha de entrega estimada |
| **FORC** | Número del caso asignado por la aseguradora al siniestro |
| **COT** | Número de Cotización |
| **F_AUT** | Fecha de Autorización de la CIA |
| **MO** | Mano de Obra |
| **RTO** | Repuesto |
| **INS_PINT** | Insumos de Pintura (se cobra con 25% markup sobre costo) |
| **TERCERO** | Trabajo subcontratado a taller externo |
| **OP** | Otros gastos no clasificados |
| **CIA** | Compañía de seguros / aseguradora |
| **LYP** | Latonería y Pintura (área del taller) |
| **VFT** | Vehículo Fuera del Taller |
| **PTE** | Pendiente (prefijo de estados: PTE_COTIZACION, etc.) |
| **LAT** | Latonero (técnico que trabaja el metal) |
| **PREP** | Preparador (masilla, lijado antes de pintar) |
| **PINT** | Pintor |
| **MEC** | Mecánico |
| **ELEC** | Electricista del vehículo |
| **SCANNER** | Diagnóstico electrónico con escáner |
| **AA** | Técnico de Aire Acondicionado |
| **B/R/G** | Bueno / Regular / Malo (inventario del vehículo) |
| **Tipo A** | Cliente que paga MO + Repuestos + Insumos (mayoría) |
| **Tipo B** | Cliente que pone sus propios repuestos (ZURICH, QUALITAS) |
| **OPORTUNO** | Si la OT se entregó antes o en la fecha prometida |
| **META_CIA** | Meta de días de la aseguradora: Leve=5, Medio=10, Fuerte=13 |

---

## LÓGICA DE NEGOCIO CRÍTICA — FÓRMULAS EXACTAS

Estas fórmulas vienen del Excel de gestión actual y deben replicarse **exactas**:

```php
// En: app/Services/OTService.php

// Horas Artesano
$ha = $total_ot / $tarifa_hora_cliente;

// Días Reales (siempre redondear hacia arriba)
$dr = (int) ceil($ha / 8 * 1.5);

// Tamaño del Golpe
$tg = match(true) {
    $dr <= 5  => 'Leve',
    $dr <= 10 => 'Medio',
    default   => 'Fuerte',
};

// Meta CIA según TG
$meta_cia = match($tg) {
    'Leve'   => $empresa->meta_dias_leve,   // default 5
    'Medio'  => $empresa->meta_dias_medio,  // default 10
    'Fuerte' => $empresa->meta_dias_fuerte, // default 13
};

// Salida Estimada (solo días hábiles, descontando festivos Colombia)
// Usar método workday() del OTService
$salida_estimada = $this->workday($fecha_inicio_proceso, $dr);

// Total según Tipo de cliente
$total = $valor_mo + $valor_insumos_pint + $valor_terceros + $valor_op;
if ($empresa->tipo === 'A') {
    $total += $valor_rto; // Tipo A incluye repuestos
}
// Tipo B NO incluye repuestos

// Semáforo (recalcular siempre en tiempo real)
$semaforo = match(true) {
    in_array($estado_proceso, ['ENTREGADO','NO_AUTORIZADO','ORDEN_ANULADA',
        'PERDIDA_TOTAL','VFT','GARANTIA','ARREGLO_DIRECTO']) => 'OK',
    is_null($salida_estimada) => 'SIN_FECHA',
    $salida_estimada < today() => 'INCUMPLIDO',
    $salida_estimada->isToday() => 'ENTREGAR_HOY',
    default => 'A_TIEMPO',
};

// WORKDAY: suma N días hábiles descontando sábados, domingos y festivos
private function workday(Carbon $desde, int $dias): Carbon {
    $festivos = Festivo::pluck('fecha')->map(fn($f) => $f->format('Y-m-d'))->toArray();
    $fecha = $desde->copy();
    $sumados = 0;
    while ($sumados < $dias) {
        $fecha->addDay();
        if (!$fecha->isWeekend() && !in_array($fecha->format('Y-m-d'), $festivos)) {
            $sumados++;
        }
    }
    return $fecha;
}
```

---

## ROLES Y PERMISOS

| Rol | Constante | Acceso |
|---|---|---|
| Administrador | `ADMIN` | Todo el sistema |
| Coordinador | `COORDINADOR` | Torre de control, asignar técnicos, autorización, repuestos, producción |
| Cotizador | `COTIZADOR` | Ver OTs en PTE_COTIZACION, crear/editar cotizaciones |
| Recepción | `RECEPCION` | Crear OTs, registrar inventario vehículo |
| Técnico | `TECNICO` | Solo sus OTs asignadas: iniciar/comentar/finalizar |

Un usuario puede tener múltiples roles. Implementar con campo `roles` tipo JSON en tabla `usuarios`.
Middleware `CheckRole` verifica acceso por ruta.

---

## ESTADOS DE UNA OT — FLUJO COMPLETO

```
ENTRADA:
PTE_COTIZACION → PTE_AUTORIZACION → PTE_ORDEN → PTE_REPUESTOS
→ RTO_INSTALADO → EN_PROCESO → PROGRAMADO_ENTREGA → ENTREGADO

SALIDAS ESPECIALES:
NO_AUTORIZADO / ORDEN_ANULADA / PERDIDA_TOTAL / VFT /
GARANTIA / ARREGLO_DIRECTO / ENTREGA_PARCIAL /
EN_OTRO_TALLER / PTE_RETIRO
```

**Flujo por tipo de cliente:**
- Particular: `PTE_COTIZACION → EN_PROCESO → ENTREGADO`
- Aseguradora: flujo completo con autorización y repuestos
- Flota: igual que aseguradora pero aprobación más rápida

**Automatismo crítico:** Cuando el último técnico marca su trabajo como `FINALIZADO` en `trabajo_tecnico`, el sistema automáticamente cambia `estado_proceso` de la OT a `PROGRAMADO_ENTREGA` y registra en `historial_ot`.

---

## TABLAS DE LA BASE DE DATOS

### Tablas principales (en orden de dependencia)
```
1.  festivos
2.  marcas_vehiculo
3.  modelos_vehiculo          → FK: marcas_vehiculo
4.  empresas_cliente
5.  catalogo_mo               → FK: marcas_vehiculo, modelos_vehiculo
6.  users                     → tabla auth de Laravel
7.  tecnicos                  → FK: users (nullable)
8.  clientes_persona
9.  vehiculos                 → FK: marcas_vehiculo, modelos_vehiculo, clientes_persona
10. ordenes_trabajo           → FK: users, vehiculos, clientes_persona, empresas_cliente, tecnicos(×5)
11. inventario_vehiculo       → FK: ordenes_trabajo
12. fotos_ot                  → FK: ordenes_trabajo, users
13. secuencias                → control de numeración OT y COT
14. cotizaciones              → FK: ordenes_trabajo, users
15. items_cotizacion_mo       → FK: cotizaciones, catalogo_mo
16. items_cotizacion_suministro → FK: cotizaciones
17. trabajo_tecnico           → FK: ordenes_trabajo, tecnicos
18. historial_ot              → FK: ordenes_trabajo, users
19. entregas_parciales        → FK: ordenes_trabajo
20. pagos_tecnicos            → FK: tecnicos, users, ordenes_trabajo
```

### Columnas clave de ordenes_trabajo
`numero_ot, area, id_cliente_persona, id_empresa_cliente, id_vehiculo,
km_ingreso, referencia_forc, llaves_entregadas, documentos_entregados,
ingreso_grua, nivel_combustible, estado_proceso, estado_semaforo, tg, dr, ha,
fecha_ingreso, fecha_cotizacion, fecha_autorizacion, fecha_llegada_ultimo_rto,
fecha_inicio_proceso, fecha_terminacion, fecha_entrega_cliente, salida_estimada,
valor_mo, valor_rto, valor_insumos_pint, valor_terceros, valor_op, num_piezas,
total, pasado_a_facturar, tecnico_lat, tecnico_prep, tecnico_pint, tecnico_mec,
tecnico_elec, tiene_scanner, costo_mo, costo_rto, costo_insumos, costo_total,
observaciones, creado_por, actualizado_por`

---

## DATOS SEMILLA — VALORES REALES DEL TALLER

### Empresas cliente con tarifa (seeder)
```
PERSONAL ($50.000/h, TipoA), RENTING ($53.250/h, TipoA),
SURA ($70.403/h, TipoA), BOLIVAR ($59.171/h, TipoA),
COLPATRIA ($45.500/h, TipoA), AUTOSEGURO ($53.250/h, TipoA),
SOLIDARIA ($29.000/h, TipoA), ZURICH ($50.000/h, TipoB),
QUALITAS ($50.000/h, TipoB), MAREAUTOS ($50.000/h, TipoA),
DHL ($50.000/h, TipoA), UNINORTE ($50.000/h, TipoA),
FRITOLAY ($50.000/h, TipoA), NETCOL ($50.000/h, TipoA),
PERSONAL SEGUROS ($50.000/h, TipoA), HYUNDAUTOS ($50.000/h, TipoA),
TALLERES DEL NORTE ($50.000/h, TipoA)
```

### Técnicos reales (seeder)
```
Edwin H (LAT), Alvaro M (LAT), Larry A (LAT),
Felix V (PREP,PINT), Martin N (PREP,PINT), Alberto P (PREP),
Luis (PREP), Willian (PREP,PINT), Emanuel (PREP),
Jose R (MEC), Benjamin (MEC), Hector C (MEC), Keiner C (MEC,AA),
Fabio M (ELEC)
```

### Secuencias iniciales (continúan del Excel real)
```
OT: empieza en 49632
COTIZACION: empieza en 137126
```

### Festivos Colombia
Precargar 2025 y 2026 completos.

### Marcas más frecuentes del taller (seeder)
```
RENAULT (Duster, Logan, Kangoo, Alaskan, Sandero, Oroch)
CHEVROLET (Onix, Tracker, Sail, Captiva, Cruze)
KIA (Rio, Picanto, Sportage)
MAZDA (Mazda 2, Mazda 3, CX-5)
SUZUKI (Swift, Vitara)
TOYOTA (Hilux, Corolla)
HYUNDAI (Tucson, Elantra, Accent)
FORD, VOLKSWAGEN, NISSAN, HONDA, BMW
```

---

## CATÁLOGO DE MANO DE OBRA — ESTRUCTURA

El catálogo tiene 3 niveles jerárquicos:
- **Nivel 1**: Genérico (aplica a todos los vehículos)
- **Nivel 2**: Por marca (Renault, Chevrolet, etc.)
- **Nivel 3**: Por marca + modelo específico (Renault Duster)

Cuando el cotizador abre una OT, el sistema muestra automáticamente los ítems del catálogo filtrados para esa marca/modelo (de más específico a más general). El cotizador puede modificar el precio en la cotización sin afectar el catálogo base. El precio del catálogo es solo de referencia.

---

## ALERTAS AUTOMÁTICAS (verificar en cada carga del dashboard)

| Condición | Días | Urgencia |
|---|---|---|
| Sin cotización desde ingreso | > 2 días hábiles | Amarilla |
| Esperando autorización CIA | > 5 días | Amarilla |
| Autorizada sin iniciar proceso | > 1 día | Naranja |
| Repuestos sin llegar | > 10 días desde autorización | Naranja |
| OT INCUMPLIDA | Salida estimada < hoy | Roja |
| Entregar hoy | Salida estimada = hoy | Amarilla |
| Entrega parcial sin resolver | > 30 días | Naranja |

---

## FASES DE DESARROLLO

Completar en orden. No avanzar a la siguiente fase sin confirmar la anterior.

| Fase | Módulo | Estado |
|---|---|---|
| 1 | Setup completo: Homebrew, PHP 8.2, Composer, Herd, DBngin, Laravel 11.53.1, GitHub, Tabler layout, Auth, Roles, Dashboard | ⬜ Pendiente |
| 2 | Recepción de vehículos: crear OT, inventario B/R/G, fotos, búsqueda por placa | ⬜ Pendiente |
| 3 | Torre de Control: semáforo, filtros, tabla principal | ⬜ Pendiente |
| 4 | Panel del Técnico: mis tareas, iniciar/comentar/finalizar, avance por OT | ⬜ Pendiente |
| 5 | Catálogo de MO: CRUD por marca/modelo, niveles jerárquicos | ⬜ Pendiente |
| 6 | Cotización: selección de ítems del catálogo, suministros, cálculos automáticos, PDF | ⬜ Pendiente |
| 7 | Autorización y Repuestos: registro de fechas, validaciones, estados | ⬜ Pendiente |
| 8 | Liquidación de Técnicos: por mes, avances, saldo, PDF recibo | ⬜ Pendiente |
| 9 | Producción y KPIs: gráficas, comparativa vs meta, exportar Excel | ⬜ Pendiente |
| 10 | Entregas Parciales y Alertas automáticas | ⬜ Pendiente |
| 11 | Migración del Excel histórico al sistema | ⬜ Pendiente |

---

## REGLAS QUE NUNCA SE PUEDEN VIOLAR

1. Nunca usar `{!! !!}` en Blade salvo PDF o HTML explícitamente confiable
2. Nunca poner lógica de negocio en Controllers — va en Services
3. Nunca crear assets con npm build — solo CDN
4. Nunca saltar una fase sin confirmar con el desarrollador
5. Nunca usar fechas sin validar que no sean futuras ni anteriores a la fase previa
6. Nunca comprometer el `.env` en git
7. Nunca modificar los precios del catálogo desde la cotización — solo la cotización tiene su precio propio
8. El campo `estado_semaforo` siempre se recalcula, nunca se edita manualmente
9. Los nombres de técnicos siempre vienen del catálogo — nunca texto libre
10. Todo cambio de estado de OT genera un registro en `historial_ot`
