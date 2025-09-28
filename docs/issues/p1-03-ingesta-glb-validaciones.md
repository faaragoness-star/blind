# P1: Ingesta GLB y validaciones mínimas

Referencia: docs/Plugin 1 — G3d Models Manager (ingesta Glb Y Binding Técnico) — Informe.md

- [ ] Flujo de subida conforme al doc.
- [ ] Validaciones de tamaño/formatos del doc.
- [ ] Manejo de errores tal como el doc.

# 4) Admin — Secciones
#     • 4.1 Ingesta GLB
#         ◦ Subir archivo (drag&drop + checksum).
#         ◦ Metadatos: tamaño, hash, compresión (Draco), bounding boxes.
#     • 4.2 Análisis & Binding
#         ◦ Slots detectados (lista de nombres tal cual vienen del GLB).
#         ◦ Anchors obligatorios: Frame_Anchor, Temple_L_Anchor, Temple_R_Anchor, Socket_Cage (si aplica).
#         ◦ Props leídas del objeto: socket_*_mm, lug_*_mm, side, variant, mount_type, tolerancias.
#         ◦ Object name / pattern y model_code.
#     • 4.3 Previsualización
#         ◦ Viewer simple con cámara fija + wireframe toggle.
#         ◦ Golden shots (auto/forzar regeneración).
#     • 4.4 Validaciones
#         ◦ Escala (mm → escena), ejes Z↑, pivotes correctos.
#         ◦ Props obligatorias según pieza.
#         ◦ Presencia de anchors.
#         ◦ Tamaño máximo GLB.

# 8) Flujos críticos
#     • Ingesta → Análisis → Aprobación técnica → Ready → Sync a Catálogo.
#     • Rechazo con motivos y acciones sugeridas (arreglar pivote, añadir prop, re-exportar con Draco).

# 9) Errores \& Zero-mensajes
#     • Errores estándar: E_ASSET_MISSING, E_PROP_MISSING, E_ANCHOR_MISSING, E_SCALE_INVALID, E_AXES_INVALID, E_FILE_TOO_LARGE, E_SLOTS_EMPTY.
#     • En front público no aparecen; solo métricas/logs. El configurador nunca depende directamente del Admin de este plugin.

# 10) Rendimiento
#     • GLB por pieza ≤ 2–3 MB (objetivo); hard cap configurable (p. ej., 12 MB).
