# P1: Documentación

Referencia: docs/Plugin 1 — ... Informe.md

- [ ] README del plugin (copiar secciones del doc).
- [ ] Ejemplos/limitaciones exactamente como el doc.

# 1) Identidad
#     • Objetivo: recibir GLB por pieza, analizarlos y fijar el binding técnico (props, anclas, slots detectados) que usarán los demás plugins.

# 2) Visibilidad
#     • Front (público): no visible. Su “resultado” es datos confiables (URLs GLB, props mm, slots_detectados) para el visor.
#     • Admin: sí. Panel de ingesta y análisis de modelos.

# 3) Alcance y límites
#     • ✅ Ingesta de GLB uno a uno.
#     • ✅ Extracción de props y anclas; detección de slots embebidos.
#     • ✅ Generación de miniaturas/“golden shots”.
#     • ✅ Validaciones técnicas (escala/ejes/props/anchos/altos).
#     • ❌ No publica snapshots de catálogo (eso es del Catalog \& Rules).
#     • ❌ No decide reglas editoriales (material↔modelo, etc.).

# 5) Modelo de datos (resumen)
#     • Model (CPT g3d_model):
#         ◦ id (UUID), piece_type (FRAME|TEMPLE|RIMLESS)
#         ◦ file_url, file_hash, filesize_bytes, draco_enabled
#         ◦ object_name, object_name_pattern, model_code
#         ◦ Props (según pieza):
#             ▪ FRAME (FRAMED): socket_width_mm, socket_height_mm, variant, mount_type
# 
#             ▪ TEMPLE: lug_width_mm, lug_height_mm, side
# 
#             ▪ RIMLESS: mount_type, (extensibles: hole_spacing_mm, etc.)
#         ◦ tolerances: tol_w_mm, tol_h_mm (opcionales)
#         ◦ anchors_present[]
#         ◦ slots_detectados[] (strings sin normalizar; slots abiertos)
#         ◦ analysis_report (JSON), golden_shots[]
#         ◦ status: draft|analyzed|approved|ready
#         ◦ created_by, updated_by, created_at, updated_at

# 6) APIs/Contratos (ejemplos)
#     • POST /models → subir GLB (devuelve id, file_hash).
#     • POST /models/{id}/analyze → corre análisis, llena slots_detectados, anchors_present, props.
#     • GET /models/{id} → ficha técnica.
#     • GET /models?status=ready&piece_type=FRAME → listas filtradas.
#     • Evento model.analyzed (payload con id, slots_detectados, props).

# 7) RBAC
#     • Editor Técnico: subir GLB, lanzar análisis, editar metadata.
#     • QA Técnico: validar/aprobar técnico.
#     • Admin: borrar, restaurar, configurar límites (peso, props requeridas).
#     • Solo lectura: ver fichas/descargas.

# 8) Flujos críticos
#     • Ingesta → Análisis → Aprobación técnica → Ready → Sync a Catálogo.
#     • Rechazo con motivos y acciones sugeridas (arreglar pivote, añadir prop, re-exportar con Draco).

# 9) Errores \& Zero-mensajes
#     • Errores estándar: E_ASSET_MISSING, E_PROP_MISSING, E_ANCHOR_MISSING, E_SCALE_INVALID, E_AXES_INVALID, E_FILE_TOO_LARGE, E_SLOTS_EMPTY.
#     • En front público no aparecen; solo métricas/logs. El configurador nunca depende directamente del Admin de este plugin.

# 11) Observabilidad
#     • Logs JSON con request_id, model_id, file_hash.
#     • Métricas: % con anchors válidos, % con props completas, media de slots/pieza, peso medio GLB, tasa de rechazo.

# 12) Seguridad
#     • Subida autenticada, verificación de tipo/virus.
#     • Rutas privadas para originales; públicos solo los GLB “ready” en CDN.

# 13) Versionado \& Packaging
#     • g3d-models-manager-vX.Y.Z.zip con README/CHANGELOG/MANIFEST y scripts verify.* (validación post-instalación).
#     • Compatibilidad de API con N/N-1.

# 14) Backups \& DR
#     • Metadatos en DB, GLB en storage (S3/CDN).
#     • RPO ≤ 24h, RTO ≤ 4h; pruebas trimestrales.
