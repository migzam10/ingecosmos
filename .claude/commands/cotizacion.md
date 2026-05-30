# Comando: /cotizacion
# Uso: /cotizacion
# Construye o corrige el módulo de cotización (Fase 6)

Lee CLAUDE.md para recordar las reglas del módulo de Cotización.

El módulo de Cotización debe:
- Mostrar ítems del catálogo filtrados por marca/modelo de la OT (más específico primero)
- Separar claramente: Mano de Obra | Suministros/Repuestos
- Suministros: opción de marcar ítem como "CLIENTE" (el asegurado lo pone)
- Calcular en tiempo real: HA, DR, TG mientras se llenan los valores
- Mostrar TG calculado con badge de color (Leve=verde, Medio=amarillo, Fuerte=rojo)
- Mostrar Salida Estimada calculada en tiempo real
- Permitir agregar ítems manuales que no estén en el catálogo
- El precio del catálogo es de referencia: el cotizador puede cambiarlo en la cotización
- Generar PDF con DomPDF al guardar (separado en MO y Suministros)
- Al guardar: cambiar estado OT a PTE_AUTORIZACION y registrar en historial_ot
- Soporte de múltiples versiones de cotización por OT

Muestra el estado actual del módulo y lista qué falta o qué está fallando.
