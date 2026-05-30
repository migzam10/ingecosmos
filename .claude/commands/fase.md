# Comando: /fase
# Uso: /fase [número]
# Ejemplo: /fase 2
# Inicia el desarrollo de una fase específica del proyecto INGECOSMOS

Lee el archivo CLAUDE.md y revisa el estado actual de las fases.

Para la fase indicada en el argumento:

1. Confirma que la fase anterior está completada (si no es la fase 1)
2. Lista exactamente qué vas a construir en esta fase
3. Empieza con las migraciones y modelos antes de cualquier vista
4. Sigue el orden: Migrations → Models → Services → Controllers → Requests → Views → Routes
5. Al terminar la fase, ejecuta `git add . && git commit -m "feat: fase-[N] [descripción]"`
6. Actualiza el estado de la fase en CLAUDE.md de ⬜ a ✅

Antes de escribir código, muestra el plan detallado de lo que vas a construir
y espera confirmación.
