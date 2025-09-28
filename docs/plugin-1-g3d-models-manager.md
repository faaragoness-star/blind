# Plugin 1 — G3D Models Manager (ingesta GLB y binding técnico)

# 1\) Identidad

# &nbsp;   • Objetivo: recibir GLB por pieza, analizarlos y fijar el binding técnico (props, anclas, slots detectados) que usarán los demás plugins.

# 2\) Visibilidad

# &nbsp;   • Front (público): no visible. Su “resultado” es datos confiables (URLs GLB, props mm, slots\_detectados) para el visor.

# &nbsp;   • Admin: sí. Panel de ingesta y análisis de modelos.

# 3\) Alcance y límites

# &nbsp;   • ✅ Ingesta de GLB uno a uno.

# &nbsp;   • ✅ Extracción de props y anclas; detección de slots embebidos.

# &nbsp;   • ✅ Generación de miniaturas/“golden shots”.

# &nbsp;   • ✅ Validaciones técnicas (escala/ejes/props/anchos/altos).

# &nbsp;   • ❌ No publica snapshots de catálogo (eso es del Catalog \& Rules).

# &nbsp;   • ❌ No decide reglas editoriales (material↔modelo, etc.).

# 4\) Admin — Secciones

# &nbsp;   • 4.1 Ingesta GLB

# &nbsp;       ◦ Subir archivo (drag\&drop + checksum).

# &nbsp;       ◦ Metadatos: tamaño, hash, compresión (Draco), bounding boxes.

# &nbsp;   • 4.2 Análisis \& Binding

# &nbsp;       ◦ Slots detectados (lista de nombres tal cual vienen del GLB).

# &nbsp;       ◦ Anchors obligatorios: Frame\_Anchor, Temple\_L\_Anchor, Temple\_R\_Anchor, Socket\_Cage (si aplica).

# &nbsp;       ◦ Props leídas del objeto: socket\_\*\_mm, lug\_\*\_mm, side, variant, mount\_type, tolerancias.

# &nbsp;       ◦ Object name / pattern y model\_code.

# &nbsp;   • 4.3 Previsualización

# &nbsp;       ◦ Viewer simple con cámara fija + wireframe toggle.

# &nbsp;       ◦ Golden shots (auto/forzar regeneración).

# &nbsp;   • 4.4 Validaciones

# &nbsp;       ◦ Escala (mm → escena), ejes Z↑, pivotes correctos.

# &nbsp;       ◦ Props obligatorias según pieza.

# &nbsp;       ◦ Presencia de anchors.

# &nbsp;       ◦ Tamaño máximo GLB.

# &nbsp;   • 4.5 Estado

# &nbsp;       ◦ Borrador → Analizado → Aprobado técnico → Listo para Catálogo.

# &nbsp;   • 4.6 Historial

# &nbsp;       ◦ Versiones del mismo modelo (hashes, diffs técnicos).

# &nbsp;   • 4.7 Export/Sync

# &nbsp;       ◦ Solo de datos técnicos hacia Catalog \& Rules.

# 5\) Modelo de datos (resumen)

# &nbsp;   • Model (CPT g3d\_model):

# &nbsp;       ◦ id (UUID), piece\_type (FRAME|TEMPLE|RIMLESS)

# &nbsp;       ◦ file\_url, file\_hash, filesize\_bytes, draco\_enabled

# &nbsp;       ◦ object\_name, object\_name\_pattern, model\_code

# &nbsp;       ◦ Props (según pieza):

# &nbsp;           ▪ FRAME (FRAMED): socket\_width\_mm, socket\_height\_mm, variant, mount\_type

# 

# &nbsp;           ▪ TEMPLE: lug\_width\_mm, lug\_height\_mm, side

# 

# &nbsp;           ▪ RIMLESS: mount\_type, (extensibles: hole\_spacing\_mm, etc.)

# &nbsp;       ◦ tolerances: tol\_w\_mm, tol\_h\_mm (opcionales)

# &nbsp;       ◦ anchors\_present\[]

# &nbsp;       ◦ slots\_detectados\[] (strings sin normalizar; slots abiertos)

# &nbsp;       ◦ analysis\_report (JSON), golden\_shots\[]

# &nbsp;       ◦ status: draft|analyzed|approved|ready

# &nbsp;       ◦ created\_by, updated\_by, created\_at, updated\_at

# 6\) APIs/Contratos (ejemplos)

# &nbsp;   • POST /models → subir GLB (devuelve id, file\_hash).

# &nbsp;   • POST /models/{id}/analyze → corre análisis, llena slots\_detectados, anchors\_present, props.

# &nbsp;   • GET /models/{id} → ficha técnica.

# &nbsp;   • GET /models?status=ready\&piece\_type=FRAME → listas filtradas.

# &nbsp;   • Evento model.analyzed (payload con id, slots\_detectados, props).

# Nota: los slots se reportan “tal cual” vienen del GLB (no hay enum); el mapeo editorial ocurre en el Admin de Catalog \& Rules.

# 7\) RBAC

# &nbsp;   • Editor Técnico: subir GLB, lanzar análisis, editar metadata.

# &nbsp;   • QA Técnico: validar/aprobar técnico.

# &nbsp;   • Admin: borrar, restaurar, configurar límites (peso, props requeridas).

# &nbsp;   • Solo lectura: ver fichas/descargas.

# 8\) Flujos críticos

# &nbsp;   • Ingesta → Análisis → Aprobación técnica → Ready → Sync a Catálogo.

# &nbsp;   • Rechazo con motivos y acciones sugeridas (arreglar pivote, añadir prop, re-exportar con Draco).

# 9\) Errores \& Zero-mensajes

# &nbsp;   • Errores estándar: E\_ASSET\_MISSING, E\_PROP\_MISSING, E\_ANCHOR\_MISSING, E\_SCALE\_INVALID, E\_AXES\_INVALID, E\_FILE\_TOO\_LARGE, E\_SLOTS\_EMPTY.

# &nbsp;   • En front público no aparecen; solo métricas/logs. El configurador nunca depende directamente del Admin de este plugin.

# 10\) Rendimiento

# &nbsp;   • GLB por pieza ≤ 2–3 MB (objetivo); hard cap configurable (p. ej., 12 MB).

# &nbsp;   • p95 análisis ≤ 2 s para 3 MB; miniaturas ≤ 1 s p95 (cacheadas).

# 11\) Observabilidad

# &nbsp;   • Logs JSON con request\_id, model\_id, file\_hash.

# &nbsp;   • Métricas: % con anchors válidos, % con props completas, media de slots/pieza, peso medio GLB, tasa de rechazo.

# 12\) Seguridad

# &nbsp;   • Subida autenticada, verificación de tipo/virus.

# &nbsp;   • Rutas privadas para originales; públicos solo los GLB “ready” en CDN.

# &nbsp;   • Auditoría 90 días (quién sube/edita/aprueba).

# 13\) Versionado \& Packaging

# &nbsp;   • g3d-models-manager-vX.Y.Z.zip con README/CHANGELOG/MANIFEST y scripts verify.\* (validación post-instalación).

# &nbsp;   • Compatibilidad de API con N/N-1.

# 14\) Backups \& DR

# &nbsp;   • Metadatos en DB, GLB en storage (S3/CDN).

# &nbsp;   • RPO ≤ 24h, RTO ≤ 4h; pruebas trimestrales.

# 15\) QA \& Checklists

# &nbsp;   • Pre-ready: anchors, props obligatorias, slots\_detectados≠∅, golden shots, escala/ejes OK, peso OK.

# &nbsp;   • Post-ready: endpoint GET /models/{id} devuelve todo; sincroniza con Catalog \& Rules sin warnings.

