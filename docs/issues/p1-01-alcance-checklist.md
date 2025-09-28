# P1: Alcance y checklist de entregables

Referencia: docs/Plugin 1 — G3d Models Manager (ingesta Glb Y Binding Técnico) — Informe.md

- [ ] Alcance confirmado (sin añadir nada fuera del doc).
- [ ] Criterios de done copiados del doc.
- [ ] Plan de pruebas copiado del doc.

# 3) Alcance y límites
#     • ✅ Ingesta de GLB uno a uno.
#     • ✅ Extracción de props y anclas; detección de slots embebidos.
#     • ✅ Generación de miniaturas/“golden shots”.
#     • ✅ Validaciones técnicas (escala/ejes/props/anchos/altos).
#     • ❌ No publica snapshots de catálogo (eso es del Catalog \& Rules).
#     • ❌ No decide reglas editoriales (material↔modelo, etc.).

# 4) Admin — Secciones
#     • 4.5 Estado
#         ◦ Borrador → Analizado → Aprobado técnico → Listo para Catálogo.

# 15) QA \& Checklists
#     • Pre-ready: anchors, props obligatorias, slots_detectados≠∅, golden shots, escala/ejes OK, peso OK.
#     • Post-ready: endpoint GET /models/{id} devuelve todo; sincroniza con Catalog \& Rules sin warnings.
