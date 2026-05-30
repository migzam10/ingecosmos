# Comando: /fix
# Uso: /fix [descripción del problema]
# Ejemplo: /fix el semáforo no cambia de color cuando la OT está incumplida
# Diagnóstica y corrige un problema específico del proyecto

Lee CLAUDE.md para entender el contexto del proyecto antes de depurar.

Pasos para diagnosticar:
1. Identifica qué módulo/fase está involucrado según la descripción
2. Revisa el archivo relevante (model, service, controller, view)
3. Verifica que la lógica de negocio respeta las fórmulas del CLAUDE.md
4. Muestra el código actual problemático
5. Explica qué está fallando y por qué
6. Propone la corrección con el código corregido
7. Verifica que la corrección no rompe otras partes del sistema

Reglas adicionales al hacer fix:
- No cambiar lógica de negocio sin confirmar con el desarrollador
- No modificar migraciones que ya tienen datos — crear nueva migration de alteración
- Si el fix involucra JavaScript, mantenerlo en vanilla JS
- Después de cada fix: `git add . && git commit -m "fix: [descripción corta]"`
