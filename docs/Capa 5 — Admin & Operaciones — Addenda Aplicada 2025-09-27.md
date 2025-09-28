Capa 5 — Admin \& Operaciones (ACTUALIZADA — Slots abiertos)

Objetivo Permitir que el equipo editorial suba GLB uno a uno, defina Material/Color/Textura/Acabado, reglas y encaje, previsualice con Zero-mensajes y publique snapshots inmutables con control de versiones, auditoría y calidad asegurada.

Principio ACTUALIZADO sobre slots

• Slots abiertos: el GLB puede traer cualquier slot embebido con cualquier nombre.

• Los slots son identificadores técnicos del GLB; su uso real depende del Admin/editorial.

• En Admin se definen controles por slot (material/color/textura/acabado/shader\_params) con defaults y, opcionalmente, affects\_sku:true|false por control. El slot es el dónde; los controles el qué.

Roles y permisos (RBAC)

Editor: Crear/editar en Borrador, cargar GLB, gestionar i18n local. No puede publicar ni borrar versiones.

QA/Revisor: Ejecutar Validador, marcar Listo, aprobar cambios. No puede editar reglas ni publicar.

Publicador: Crear Snapshot, Publicar/Rollback. No puede editar catálogo.

Admin: Configurar firma, caducidades, orígenes permitidos, backups, SSO/2FA.

Estados y flujo de trabajo editorial Borrador → En revisión → Aprobado QA → Staging → Publicado. Transiciones trazadas con quién/cuándo/qué y diff por campo.

Módulos del Admin (pantallas) 3.1 Modelos (GLB): Ingesta, hash, análisis de props y materiales. 3.2 Materiales: Defaults y reglas de seguridad. 3.3 Colores: Hex normalizado. 3.4 Texturas: Embebida (GLB) o Generada (parámetros PBR) con campo slot string libre (no enum). 3.5 Acabados: Lista discreta i18n. 3.6 Reglas: Matrices Material→Modelos/Colores/Texturas y encaje. 3.7 i18n: Hasta 18 idiomas; import/export CSV/JSON. 3.8 Previsualización: Sandbox con snapshot de trabajo. 3.9 Publicación: Validador, diff, Snapshot. 3.10 Versiones \& Auditoría: Lista, rollback, cambios. 3.11 Configuración: Firma, caducidad, CORS, backups. 3.12 Slots (mapeo editorial): detectar slots embebidos en GLB por pieza, activar/desactivar su exposición en UI y definir controles por slot (material/color/textura/acabado/shader\_params), defaults, visibilidad/orden y affects\_sku por control.

Pipeline de activos (GLB) Ingesta → Hash → Extraer props → Generar miniaturas → Validaciones → Subida CDN.

Validadores 5.1 Bloqueantes: IDs inválidos, referencias rotas, props ausentes, matrices incompletas. 5.2 Avisos: GLB pesado, i18n incompleto, sin miniaturas. No validar nombre de slot contra listas fijas. Warning si un slot configurado en Admin no existe en slots\_detectados del modelo.

Previsualización / QA Stress test encaje, zero-mensajes, snapshots comparativos, latencia <200 ms.

Versionado y publicación Snapshot inmutable + Versión catálogo. Publicación inmediata o programada. Rollback activo.

Import/Export GLB uno a uno. i18n CSV/JSON. Reglas JSON. Backup snapshot+activos.

Integraciones Endpoints /validate-sign y /verify. Precio/stock opcional. Webhooks al publicar.

Firma y caducidad Firma sig.vN, caducidad 30 días. Rotación N/N+1. Ed25519 recomendado.

Auditoría y logs Registro completo de acciones. Logs validador. Panel calidad catálogo. Retención 90 días.

Rendimiento y QA operacional GLB ≤ 2–3 MB. Texturas 256–2048 px. Matriz dispositivos. Golden shots.

Seguridad SSO/2FA, RBAC estricto, TLS, CSP, sin PII en payloads.

Backups \& DR Backup diario, retención 90 días, restauración snapshot. DR test trimestral.

Checklists operativas Antes de publicar: IDs correctos, props completos, i18n base lista, QA sandbox OK. Después de publicar: Cache/CDN invalidada, monitor validate-sign.

Plantillas (copy-paste) Definiciones JSON para Modelo, Material, Color, Textura (con slot string libre), Reglas, Encaje, Snapshot, Versión catálogo.

Roadmap preparado Nuevas piezas, morphs extendidos, LODs, A/B de orden Color↔Textura, comparador visual, colecciones.

Glosario Pieza, Modelo, Binding, Textura embebida/generada, Snapshot, Zero-mensajes, Slot (GLB), Controles por slot (Admin).



Addenda aplicada — 2025-09-27 (sin ejemplos)

Parejas de Patillas

&nbsp;   • UI tipo tabla: <pair\_id> → left\_model\_id, right\_model\_id.

&nbsp;   • Configurar pair\_controls (por defecto: sync\_controls=\["material","color","textura"], unsynced\_controls=\["acabado"]).

Validador editorial (checks)

&nbsp;   • left\_model\_id.side == L y right\_model\_id.side == R.

&nbsp;   • Aviso si alguna pareja incumple matrices (Material↔Modelo), encaje o slots.

Checklist operativa (añadir)

&nbsp;   • Parejas L/R definidas y consistentes antes de publicar snapshot.

&nbsp;   • A/B del toggle “Acabado por lado” configurado si aplica.

