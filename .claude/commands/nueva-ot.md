# Comando: /nueva-ot
# Uso: /nueva-ot
# Construye o corrige el módulo de creación de OT (Fase 2)

Lee CLAUDE.md para recordar las reglas del módulo de Recepción.

El módulo de Nueva OT debe:
- Buscar vehículo por placa via AJAX antes de llenar el formulario
- Si el vehículo existe, precargar todos los campos del propietario
- Inventario B/R/G con los 26 ítems exactos del formulario físico
- Gauge de nivel de combustible
- Checkboxes: llaves entregadas, documentos, ingresó en grúa
- Campo referencia FORC (número caso aseguradora)
- Al guardar: número OT correlativo desde secuencias, estado inicial PTE_COTIZACION
- Registro en historial_ot con usuario y timestamp
- Diseño mobile-first: formulario en una columna en móvil, dos columnas en PC

Muestra el estado actual del módulo y lista qué falta o qué está fallando.
