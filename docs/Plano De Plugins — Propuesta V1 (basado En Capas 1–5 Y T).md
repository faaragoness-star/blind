Plano de Plugins — Propuesta v2 (Enterprise)

Decisión tomada: mantener 6 plugins (4 core + 2 helpers) con overlay solo visual en el tema hijo. Arquitectura alineada con Capas 1–5 y T; slots abiertos; Zero‑mensajes; snapshot inmutable; firma Ed25519.

\## 0) Resumen ejecutivo - Objetivo: configurador 3D hiperpersonalizable con operación 24/7 y alto volumen. - Mapa definitivo (6 plugins): 1) G3D Models Manager — ingesta GLB y binding técnico. 2) G3D Catalog \& Rules — reglas editoriales, i18n, snapshot/versión. 3) G3D Validate \& Sign — SKU determinista + firma Ed25519 + verificación. 4) Gafas3D Wizard Modal — UI/UX, visor 3D, Zero‑mensajes, screenshot. 5) G3D Admin \& Ops — RBAC, auditoría, publicación/rollback, backups/DR. 6) G3D Vendor Base Helper — vendor guard, single‑instance, rutas CDN/local. - SLOs objetivo: API core 99.95% mensual; p95 /validate-sign ≤ 150 ms; LCP/TTI ≤ 2.5 s (4G realista); errores visibles al usuario = 0 (Zero‑mensajes). - Principios: contratos SemVer; coexistencia sig.vN y sig.vN-1; sin PII en payloads; alias de usuario solo estético en URL; una sola instancia de Three/visor.

\## 1) NFRs (no funcionales) - Confiabilidad: health checks, timeouts, reintentos exponenciales, pruebas de caos trimestrales. - Rendimiento: GLB ≤ 2–3 MB/pieza; texturas 256–2048 px; cache por sku\_hash+snapshot\_id; precalentado Draco/Meshopt. - Seguridad/Compliance: TLS, CSP estricta, HSTS, RBAC + SSO/2FA en admin; Ed25519 con rotación N/N+1; auditoría 90 días. - Observabilidad: logs JSON con request\_id; métricas ui.\* y negocio; trazabilidad de publicación (quién/cuándo/qué, diffs). - Compatibilidad: SemVer en payloads; schema\_version obligatorio; rollbacks inmediatos.

2\) Mapa Capas ↔ Plugins

&nbsp;   • Capa 1 (Identificadores \& Naming) → (1) Models Manager.

&nbsp;   • Capa 2 (Schemas/Snapshot) → (2) Catalog \& Rules.

&nbsp;   • Capa 3 (Validación/Firma/Caducidad) → (3) Validate \& Sign.

&nbsp;   • Capa 4 (UI/UX Orquestación) → (4) Wizard Modal (+ overlay del tema hijo solo visual).

&nbsp;   • Capa 5 (Admin \& Operaciones) → (5) Admin \& Ops.

&nbsp;   • Capa T (3D Assets \& Export) → (1) Models Manager (ingesta/binding).

&nbsp;   • Transversal → (6) Vendor Base Helper.

\## 3) Diseño por plugin (Do / Own / Expose)

\## 4) Contratos y datos clave - Slots abiertos: nombres embebidos en GLB (ninguna lista fija). El Admin decide controles por slot y affects\_sku por control. - State/SKU: serialización canónica pieza→mat→modelo→col→tex→fin; morphs fuera del SKU por defecto. - Snapshots: objeto inmutable (snap:) y ver: de catálogo; publicación con diff/auditoría. - URLs: alias en path es estético; el backend valida solo sku, sig, snap.

5\) Seguridad, calidad y operación

&nbsp;   • Seguridad: TLS, CSP, HSTS; SSO/2FA; rotación de firma; CORS lista blanca; idempotency\_key; rate‑limit.

&nbsp;   • Calidad: validadores bloqueantes (IDs/refs/props/matrices/encaje); avisos (peso GLB, i18n incompleto); warning si un slot mapeado en Admin no existe en slots\_detectados.

&nbsp;   • Backups/DR: backup diario (retención 90 días); DR test trimestral; RPO ≤ 24 h, RTO ≤ 4 h.

\## 6) Telemetría y A/B - Eventos: ui.ready/select/autocorrect/add\_to\_cart/error/snapshot\_ready. - Dimensiones: producto\_id, pieza\_id, material\_id, modelo\_id, color\_id, textura\_id, acabado\_id, variante\_ab, device, locale, texture\_source, tint\_applied, slot\_name y, por control, control\_type/control\_value\_id. - KPI: tiempo a config, % autocorrecciones, éxito add‑to‑cart, latencias validate/verify. - A/B: orden Color↔Textura, carrusel vs grid, “Acabado por lado” (parejas L/R).

\## 7) Packaging \& versionado - Zips: slug-vMAJOR.MINOR.PATCH.zip con README, CHANGELOG, MANIFEST, scripts/verify.\*. - Compatibilidad: N / N‑1 para APIs y firma (sig.vN). - CDN: invalidación controlada tras publicar snapshot; cache por sku\_hash+snapshot\_id.

8\) Plan de implantación (por fases)

&nbsp;   1. Separación catálogo: (1) ingest/binding vs (2) reglas+snapshot.

&nbsp;   2. Integración (3) validate‑sign y adaptación de (4) al contrato; Zero‑mensajes end‑to‑end.

&nbsp;   3. Operación: (5) RBAC/auditoría/rollback/backups + (6) vendor guard; overlay al tema hijo.

&nbsp;   4. SLOs: observabilidad, pruebas de carga, tuning de cachés.



9\) Criterios de aceptación (extracto)

&nbsp;   • Slots abiertos funcionando (grupos por slot en UI cuando existan N>1).

&nbsp;   • /validate-sign p95 ≤ 150 ms en snapshot caliente.

&nbsp;   • Zero‑mensajes: autocorrección + retry silencioso; ningún error visible en flujo estándar.

&nbsp;   • Publicación con diff/auditoría y rollback inmediato.

&nbsp;   • Una sola instancia de Three/visor en toda la página.

Estado: actualizado con la decisión tomada. Si das OK, congelamos esta v2 como base y generamos los esquemas REST definitivos por plugin.

