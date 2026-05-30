# Comando: /liquidacion
# Uso: /liquidacion [mes] [año]
# Ejemplo: /liquidacion 5 2026
# Construye o ejecuta la liquidación mensual de técnicos (Fase 8)

Lee CLAUDE.md para recordar las reglas del módulo de Liquidación.

La liquidación mensual debe:
- Por cada técnico: listar todas las OTs del mes en que participó (vía trabajo_tecnico)
- Mostrar el valor de MO de cada OT como base de cálculo
- Registrar avances (pagos parciales) con fecha y monto en pagos_tecnicos
- Calcular: Total MO del mes - Avances pagados = Saldo a pagar
- Generar PDF de recibo de liquidación por técnico
- Historial de pagos anteriores por técnico
- No se puede liquidar si la OT no está en estado ENTREGADO o PROGRAMADO_ENTREGA
- El coordinador/admin es quien ejecuta la liquidación

Si se pasa mes y año como argumentos, filtrar para ese período específico.
Si no se pasan argumentos, usar el mes actual.

Muestra el estado actual del módulo y lista qué falta o está fallando.
