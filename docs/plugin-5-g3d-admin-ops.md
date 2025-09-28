# Plugin 5 — G3D Admin \& Ops (RBAC, auditoría, publicación/rollback, backups/DR)

# 1\) Identidad

# &nbsp;   • Objetivo: operar el ciclo editorial extremo a extremo: carga de modelos, reglas, previsualización y publicación de snapshots/Versiones, con permisos, auditoría, calidad y resiliencia.

# 2\) Visibilidad

# &nbsp;   • Front (público): no visible.

# &nbsp;   • Admin: sí (panel completo). Gestiona flujo, validadores, publicación, auditoría y operaciones.

# 3\) Alcance y límites

# &nbsp;   • ✅ Flujo editorial: Borrador → En revisión → Aprobado QA → Staging → Publicado.

# &nbsp;   • ✅ RBAC: Editor / QA‑Revisor / Publicador / Admin (config).

# &nbsp;   • ✅ Validadores: bloqueantes y avisos; slots abiertos (sin listas fijas); warnings coherentes.

# &nbsp;   • ✅ Previsualización QA: sandbox con snapshot de trabajo.

# &nbsp;   • ✅ Publicación/rollback: snapshot inmutable + versión de catálogo.

# &nbsp;   • ✅ Backups/DR, auditoría, logs y panel de calidad.

# &nbsp;   • ❌ No define geometría ni extrae props (eso es Models Manager).

# &nbsp;   • ❌ No firma/verifica SKUs (eso es Validate \& Sign).

# 4\) Roles y permisos (RBAC)

# &nbsp;   • Editor: crear/editar en Borrador, cargar GLB, gestionar i18n local. No publica ni borra versiones.

# &nbsp;   • QA/Revisor: ejecutar Validador, marcar Listo/Aprobado. No edita reglas ni publica.

# &nbsp;   • Publicador: crear Snapshot y Versiones (publicar/rollback). No edita catálogo.

# &nbsp;   • Admin: configurar firma, caducidades, orígenes permitidos (CORS), backups, SSO/2FA.

# 5\) Admin — Estructura / Secciones

# &nbsp;   1. Modelos (GLB) — ingesta, hash, análisis de props y materiales.

# &nbsp;   2. Materiales — defaults y reglas de seguridad.

# &nbsp;   3. Colores — HEX normalizado.

# &nbsp;   4. Texturas — embebida (GLB) o generada (parámetros PBR) con campo slot string libre (no enum).

# &nbsp;   5. Acabados — lista discreta i18n.

# &nbsp;   6. Reglas — matrices Material→Modelos/Colores/Texturas + encaje.

# &nbsp;   7. i18n — hasta 18 idiomas; import/export CSV/JSON.

# &nbsp;   8. Previsualización — sandbox con snapshot de trabajo (Zero‑mensajes).

# &nbsp;   9. Publicación — Validador, diff, Snapshot.

# &nbsp;   10. Versiones \& Auditoría — lista, rollback, historial de cambios.

# &nbsp;   11. Configuración — firma, caducidad, CORS, backups.

# &nbsp;   12. Slots (mapeo editorial) — detectar slots embebidos en GLB por pieza; activar/desactivar exposición en UI; definir controles por slot (material/color/textura/acabado/shader\_params), defaults, visibilidad/orden y affects\_sku por control.

# 6\) Pipeline de activos (GLB)

# Ingesta → Hash → Extraer props → Generar miniaturas → Validaciones → Subida CDN.

# 7\) Validadores

# &nbsp;   • Bloqueantes: IDs inválidos, referencias rotas, props ausentes, matrices incompletas.

# &nbsp;   • Avisos: GLB pesado, i18n incompleto, sin miniaturas.

# &nbsp;   • Regla de slots abiertos: no validar nombre de slot contra listas fijas.

# &nbsp;   • Warning: si un slot configurado en Admin no existe en slots\_detectados del modelo.

# 8\) Previsualización / QA

# &nbsp;   • Stress test: encaje, Zero‑mensajes, snapshots comparativos.

# &nbsp;   • Latencia objetivo: < 200 ms (primer frame estable) en sandbox.

# 9\) Publicación y versionado

# &nbsp;   • Snapshot inmutable + Versión de catálogo (ver).

# &nbsp;   • Publicación inmediata o programada; rollback activo.

# 10\) Import/Export

# &nbsp;   • GLB uno a uno (ingesta controlada).

# &nbsp;   • i18n CSV/JSON.

# &nbsp;   • Reglas JSON.

# &nbsp;   • Backups: snapshot + activos.

# 11\) Integraciones

# &nbsp;   • Endpoints: /validate-sign y /verify (precio/stock opcional).

# &nbsp;   • Webhooks al publicar.

# 12\) Firma y caducidad

# &nbsp;   • Firma sig.vN, caducidad 30 días. Rotación N/N+1. Ed25519 recomendado.

# 13\) Auditoría y logs

# &nbsp;   • Registro completo de acciones (quién/cuándo/qué) y logs del validador.

# &nbsp;   • Panel de calidad del catálogo.

# &nbsp;   • Retención 90 días.

# 14\) Rendimiento y QA operacional

# &nbsp;   • GLB ≤ 2–3 MB; texturas 256–2048 px.

# &nbsp;   • Matriz de dispositivos y golden shots.

# 15\) Seguridad

# &nbsp;   • SSO/2FA, RBAC estricto, TLS, CSP; sin PII en payloads.

# 16\) Backups \& DR

# &nbsp;   • Backup diario, retención 90 días, restauración de snapshot.

# &nbsp;   • DR test trimestral.

# 17\) Checklists operativas

# &nbsp;   • Antes de publicar: IDs correctos; props completas; i18n base lista; QA sandbox OK.

# &nbsp;   • Después de publicar: cache/CDN invalidada; monitor validate‑sign.

# 18\) Plantillas (copy‑paste)

# &nbsp;   • Definiciones JSON para Modelo, Material, Color, Textura (con slot string libre), Reglas, Encaje, Snapshot, Versión de catálogo.

# 19\) Roadmap preparado

# &nbsp;   • Nuevas piezas, morphs extendidos, LODs, A/B Color↔Textura, comparador visual, colecciones.

# 20\) Glosario

# &nbsp;   • Pieza, Modelo, Binding, Textura embebida/generada, Snapshot, Zero‑mensajes, Slot (GLB), Controles por slot (Admin).

# 

# Addenda — 2025‑09‑27 (Parejas L/R)

# &nbsp;   • UI tipo tabla: <pair\_id> → left\_model\_id, right\_model\_id.

# &nbsp;   • Configurar pair\_controls (por defecto: sync\_controls=\["material","color","textura"], unsynced\_controls=\["acabado"]).

# &nbsp;   • Validador editorial (checks): left\_model\_id.side == L y right\_model\_id.side == R.

# &nbsp;   • Checklist operativa (añadir): Parejas L/R definidas y consistentes antes de publicar snapshot. A/B del toggle “Acabado por lado” configurado si aplica.

