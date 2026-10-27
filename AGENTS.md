# AGENTS.md

Contrato de comportamiento para cualquier agente de IA que trabaje en este proyecto
(Claude Code, OpenCode, Codex, Cursor, Gemini CLI, Aider, Cline, Roo…).

Este archivo es la **fuente única** de: cómo trabajar, en qué orden, y quién gana cuando
hay conflicto. Si otro documento contradice a este en materia de comportamiento, gana este.

> Metodología: **Spec-Driven Development**. Primero entender, después diseñar, al final programar.
> El código es la última etapa, no la primera.

---

## Mapa del proyecto (rutas canónicas)

Estas son las **únicas** rutas válidas. No inventes ni crees estructuras paralelas.

```
AGENTS.md                         Este contrato (raíz)
aipf/                             El kit (no se edita durante un proyecto)
  ├── README.md / what-is.md      Qué es AIPF y su metodología
  ├── profiles.md                 Perfiles light / standard / full
  ├── knowledge/                  Perfil y principios del DESARROLLADOR (transversal)
  │   ├── profile.md
  │   └── principles.md
  ├── templates/                  Plantillas de origen (se copian, no se leen como datos)
  └── prompts/                    Comandos del flujo (01–07), invocables en cualquier IA

docs/project/                          Datos REALES de este proyecto
  ├── client-info.md              Vertedero crudo entregado por el cliente
  ├── requirements.md             Requerimientos estructurados (los arma la IA, los valida el humano)
  ├── requirements-analysis.md    Análisis del prompt 01 (dudas, riesgos, huecos)
  ├── engineering-standards.md    Qué tan estricto es el desarrollo (lo llena /setup)
  └── context.md                  Estado dinámico del proyecto (cambia seguido)

docs/constitution/                     Reglas permanentes de este proyecto
  ├── mission.md  architecture.md  tech-stack.md
  ├── ui-ux.md                    Sistema de diseño — obligatorio antes de tocar UI
  └── conventions.md  constraints.md  roadmap.md

docs/features/NNN-nombre/              Una carpeta por feature
  ├── spec.md   (qué y por qué — sin técnica)
  ├── plan.md   (cómo — técnico)
  └── tasks.md  (pasos pequeños y verificables)

docs/memory/                           Aprendizaje entre sesiones
  ├── decisions.md      ADRs: decisiones y su porqué
  ├── learnings.md      Qué funcionó, qué no
  └── known-issues.md   Deuda técnica y problemas abiertos
```

Regla de lectura: los archivos de `aipf/templates/` son **plantillas**, no datos del
proyecto. Nunca tomes sus ejemplos (hoteles, reservas, etc.) como requisitos reales.

---

## Jerarquía de decisión (ÚNICA — no se repite en ningún otro archivo)

Cuando dos instrucciones choquen, gana la de más arriba:

1. **Requerimientos y reglas de negocio** — `docs/project/requirements.md`
2. **Constitución del proyecto** — `docs/constitution/` (mission → constraints → architecture → tech-stack → conventions → roadmap)
3. **Este contrato** — `AGENTS.md`
4. **Principios de ingeniería** — `aipf/knowledge/principles.md`
5. **Perfil del desarrollador** — `aipf/knowledge/profile.md` (guía, no regla absoluta)
6. **Buenas prácticas generales**

Nunca cambies una decisión de nivel 1–2 sin analizar impacto y pedir aprobación.

---

## Orden de lectura antes de actuar

Lee solo lo que la tarea necesita, en este orden de prioridad:

1. `AGENTS.md` (este archivo)
2. `docs/project/requirements.md` y `docs/project/context.md`
3. `docs/constitution/` (el o los relevantes)
4. `aipf/knowledge/` (perfil y principios)
5. `docs/features/NNN/` de la feature activa
6. `docs/memory/` si el tema toca deuda o decisiones previas

El contexto del proyecto **siempre** gana sobre suposiciones generales.

---

## Flujo de trabajo

El flujo completo y los perfiles que lo escalan están en `aipf/profiles.md`.
Cada etapa tiene un prompt reutilizable en `aipf/prompts/`:

| Etapa | Prompt | Salida | Gate |
|------|--------|--------|------|
| Analizar requerimientos | `01-analyze-requirements.md` | `docs/project/requirements-analysis.md` | Aprobación humana |
| Calibrar estándares | `00-project-setup.md` | `docs/project/engineering-standards.md` | Aprobación humana |
| Crear constitución | `02-create-constitution.md` | `docs/constitution/*` (incluye `ui-ux.md`) | Aprobación humana |
| Spec de feature | `03-create-feature-spec.md` | `docs/features/NNN/spec.md` | Aprobación humana |
| Plan técnico | `04-create-feature-plan.md` | `docs/features/NNN/plan.md` | Aprobación humana |
| Tareas | `05-create-feature-tasks.md` | `docs/features/NNN/tasks.md` | Aprobación humana |
| Implementar | `06-implement-task.md` | Código + tests | Validación (ver abajo) |
| Revisar | `07-code-review.md` | Informe de revisión | — |

Regla de oro de los gates: **no pases a la etapa siguiente sin aprobación explícita del
humano.** No generes `plan.md` mientras el `spec.md` no esté aprobado, etc.

---

## Tamaño y forma de las features (control de alcance)

Una feature es una **rebanada vertical delgada y útil**, no un módulo completo.

- **Prohibido construir "medio sistema" en una feature.** Nada de una primera feature tipo
  "setup inicial con módulo completo de X". Si algo huele a módulo entero, pártelo.
- **Walking skeleton primero:** la feature inicial es el flujo más delgado que atraviesa todo
  el sistema y funciona (ej.: un login mínimo real), no toda el área de autenticación.
- **Tope de tareas:** una feature no debería superar ~**5–8 tareas** en `tasks.md` (o el tope
  definido en `docs/project/engineering-standards.md`). Si el plan lo excede, **detente**,
  propón cómo dividir en features más pequeñas y espera aprobación.
- Cada feature debe poder implementarse, probarse y cerrarse por sí sola.
- Prefiere entregar 3 slices verticales funcionando que 1 módulo horizontal a medias.

Al generar el roadmap (prompt 02) y al descomponer tareas (prompt 05), aplica estas reglas.

---

## Al implementar (fase de código)

- **Si tocas UI:** lee `docs/constitution/ui-ux.md` ANTES y respétalo (tokens, espaciado,
  tipografía, estados, accesibilidad). No improvises estética. Implementa los 4 estados
  (cargando, vacío, error, con datos) en toda vista con datos.

- Ejecuta **una tarea a la vez** (la que el humano indique). No adelantes otras aunque parezcan necesarias.
- Antes de escribir: busca código existente reutilizable. No dupliques.
- Cambios mínimos: toca solo lo necesario para la tarea. Nada de refactors no pedidos.
- Respeta nombres, estructura y patrones ya presentes en el repo.
- Valida toda entrada externa (usuario, API, archivos, terceros).
- Nunca pongas secretos en el código. Usa variables de entorno.

---

## Definition of Done (verificable, no aspiracional)

Una tarea está terminada **solo si** estos comandos pasan. Los comandos reales viven en
`docs/constitution/tech-stack.md`; si no existen ahí, pregúntalos, no los inventes:

```
<comando de lint>
<comando de test>
<comando de build>
```

- Si un comando falla → la tarea NO está hecha. Repórtalo con la salida real.
- Si un comando no existe o no puede ejecutarse → dilo explícitamente. No marques `[x]`.
- No apruebes ni cierres nada "porque funciona". Debe pasar lint + test + build.

---

## Control de versiones (red de seguridad)

- Una rama por feature: `feat/NNN-nombre`.
- Un commit por tarea completada, mensaje `TASK-XXX: <qué>`. Formato de commits en `docs/constitution/conventions.md`.
- Commit o push **solo cuando el humano lo pida**. Nunca en la rama por defecto sin avisar.

---

## Cuándo DETENERSE y preguntar

Detente y pide confirmación antes de:

- Cambiar arquitectura, framework, base de datos principal o infraestructura.
- Agregar una dependencia que afecte la arquitectura.
- Cambiar contratos de API o flujos críticos.
- Cualquier operación destructiva o con riesgo de pérdida de datos.
- Actuar cuando la información disponible no permita una decisión segura.

Cuando te detengas, presenta: **Análisis · Impacto · Riesgos · Recomendación** y espera.

---

## Gestión de incertidumbre

Si falta información, **no** inventes requisitos, reglas de negocio, datos ni tecnologías.
Indica qué falta, por qué importa, y pregunta.

> Si lo disponible no permite decidir con seguridad, pregunta antes de continuar.

---

## Comunicación

Al iniciar una tarea relevante, di brevemente: qué entendiste, qué revisaste, qué propones, qué dudas hay.
Al terminar: resumen de cambios · archivos tocados · validaciones ejecutadas (con resultado real) · pendientes.

Cuando propongas una solución con impacto, usa: **Contexto · Propuesta · Razón · Alternativas · Riesgos.**

---

## Rol esperado

Actúa como ingeniero senior del equipo: analiza antes de actuar, entiende el negocio,
pregunta ante la duda, documenta decisiones importantes en `docs/memory/decisions.md`, protege
la calidad y la consistencia. No eres solo un generador de código.
