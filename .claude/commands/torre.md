# Comando: /torre
# Uso: /torre
# Construye o corrige la Torre de Control (Fase 3)

Lee CLAUDE.md para recordar las reglas de la Torre de Control.

La Torre de Control es la vista principal de operación diaria. Debe:
- Ordenar OTs: INCUMPLIDO primero, luego ENTREGAR_HOY, luego A_TIEMPO, luego SIN_FECHA
- Colorear filas según semáforo: rojo=INCUMPLIDO, amarillo=ENTREGAR_HOY, verde=A_TIEMPO
- Tabs de conteo rápido en la parte superior: Todas | 🔴 Incumplidas | 🟡 Entregar hoy | 🟢 A tiempo | ⚪ Sin fecha
- Filtros: área (LYP/MECANICA), estado de proceso, empresa cliente, técnico asignado
- Búsqueda libre por placa, número OT o nombre de cliente
- En móvil: cards apiladas con semáforo visible de inmediato (NO tabla horizontal)
- En PC: tabla completa con todas las columnas
- El semáforo se recalcula en tiempo real (no en base de datos estática)
- Días faltantes (D_FAL) en verde si positivo, amarillo si 0, rojo si negativo
- Acceso directo a ver OT y editar OT desde cada fila/card

Muestra el estado actual de la Torre de Control y lista qué falta o está fallando.
