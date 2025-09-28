# Plugin 4 — Gafas3D Wizard Modal (UI/UX + Visor 3D + Zero‑mensajes)

# 1\) Identidad

# &nbsp;   • Objetivo: proveer la UI visible del configurador 3D, orquestando selección por pasos (pieza→material→modelo→color→textura→acabado), aplicando slots abiertos y Zero‑mensajes, y entregando al carrito un SKU determinista obtenido vía Validate \& Sign.

# 2\) Visibilidad

# &nbsp;   • Front (público): sí, visible. Modal o página embebida con visor 3D + pasos/chips + resumen + CTA.

# &nbsp;   • Admin: no tiene admin propio; se configura leyendo el snapshot publicado de Catalog \& Rules. Ajustes visuales menores (tema, textos auxiliares) por overlay del tema hijo.

# 3\) Alcance y límites

# &nbsp;   • ✅ UI/UX de pasos y chips (incluye grupos por slot cuando existan N>1 slots en una pieza).

# &nbsp;   • ✅ Viewer 3D determinista, screenshot opcional (caché por sku\_hash).

# &nbsp;   • ✅ Autoselección (último válido → default → primer válido) y Zero‑mensajes.

# &nbsp;   • ✅ Undo/Redo (mín. 20 acciones) y accesibilidad WCAG 2.2 AA.

# &nbsp;   • ✅ Add to cart llamando a /validate-sign y manejando autocorrección + retry.

# &nbsp;   • ❌ No publica catálogo ni edita reglas (solo lectura de snapshot).

# &nbsp;   • ❌ No firma ni verifica (usa Validate \& Sign).

# 4\) Arquitectura (contratos de datos)

# &nbsp;   • Dependencias: Snapshot (Catalog \& Rules), Validate \& Sign, CDN de GLB/miniaturas.

# &nbsp;   • Estado cliente (``):

# {

# &nbsp; "producto\_id": "prod:…",

# &nbsp; "piezas": \[

# &nbsp;   {

# &nbsp;     "pieza\_id": "pieza:frame",

# &nbsp;     "material\_id": "mat:…",

# &nbsp;     "modelo\_id": "modelo:…",

# &nbsp;     "color\_id": "col:…",

# &nbsp;     "textura\_id": "tex:…",

# &nbsp;     "acabado\_id": "fin:…",

# &nbsp;     "slots": {

# &nbsp;       "MAT\_BASE": {

# &nbsp;         "material\_id": "mat:…",

# &nbsp;         "color\_id": "col:…",

# &nbsp;         "textura\_id": "tex:…",

# &nbsp;         "acabado\_id": "fin:…"

# &nbsp;       }

# &nbsp;     }

# &nbsp;   }

# &nbsp; ],

# &nbsp; "morphs": {}

# }

# &nbsp;   • Serialización canónica: pieza→mat→modelo→col→tex→fin; controles por slot incluidos solo si el control está marcado affects\_sku:true en el snapshot.

# &nbsp;   • ComputeAllowed (motor local): filtra opciones por matrices, encaje (clearances por material), safety, rules de textura y mapping editorial de slots.

# 5\) UI — Estructura y componentes

# &nbsp;   • 5.1 Layout

# &nbsp;       ◦ Desktop (≥1024px): visor 3D izquierda; pasos/chips derecha.

# &nbsp;       ◦ Mobile (<1024px): visor arriba; pasos en acordeón.

# &nbsp;       ◦ Header: título, precio opcional, compartir.

# &nbsp;       ◦ Footer fijo: CTA Add to Cart + Resumen.

# &nbsp;   • 5.2 Stepper/Tabs

# &nbsp;       ◦ ARIA tabs completos; navegación teclado (← → Home End) y activación Enter/Space.

# &nbsp;       ◦ Estado aria-selected, aria-controls y foco visible.

# &nbsp;   • 5.3 Chips

# &nbsp;       ◦ role="radiogroup" y radio items accesibles (hit‑area ≥44px, foco claro).

# &nbsp;       ◦ Grupos por slot: si una pieza tiene N>1 slots embebidos, mostrar un grupo de chips por slot (título corto con nombre técnico o label editorial) con undo/redo aislado.

# &nbsp;       ◦ Overflow: carrusel horizontal y modal de selección ampliada.

# &nbsp;   • 5.4 Visor 3D

# &nbsp;       ◦ Cámara determinista, toolbar mínima, gestos + teclado, wireframe opcional.

# &nbsp;       ◦ Fallback estático si GLB no carga; mantener último frame ante errores.

# &nbsp;       ◦ Cross‑fade entre cambios; precarga Draco/Meshopt.

# &nbsp;   • 5.5 Resumen

# &nbsp;       ◦ Plantilla i18n: {{pieza}} · {{material}} — {{color}} · {{textura}} · {{acabado}} (live‑region aria-live="polite").

# &nbsp;   • 5.6 CTA Add to Cart

# &nbsp;       ◦ Siempre habilitada; al pulsar → POST /validate-sign con autocorrección y retry (1).

# &nbsp;   • 5.7 Screenshot

# &nbsp;       ◦ JPG 1024×1024 opcional, cacheado por sku\_hash (TTL 90 días).

# 6\) Accesibilidad (WCAG 2.2 AA)

# &nbsp;   • Contraste ≥4.5:1; foco visible; navegación por teclado completa.

# &nbsp;   • Roles/ARIA en Stepper, Chips (radiogroups) y modales.

# &nbsp;   • aria-live en Resumen y contador del carrito.

# &nbsp;   • Respeta prefers-reduced-motion; animaciones 150–400 ms.

# 7\) Orquestación y lógica

# &nbsp;   • Autoselección: último válido → default → primer válido (por pieza y por slot).

# &nbsp;   • Undo/Redo: stack de 20 acciones; atajos Ctrl/Cmd+Z/Shift+Z.

# &nbsp;   • Parejas L/R (Patillas)

# &nbsp;       ◦ Selector de Pareja (pair\_id) en vez de listas L/R separadas.

# &nbsp;       ◦ Toggle “Acabado por lado”: OFF = acabado único; ON = “Acabado (Izq.)” / “Acabado (Dcha.)”.

# &nbsp;       ◦ Orquestación: al elegir pareja, asignación atómica de left\_model\_id y right\_model\_id a patilla\_L/patilla\_R.

# &nbsp;       ◦ Sincroniza automáticamente material/color/textura; no sincroniza acabado.

# 8\) URLs y deep‑linking

# &nbsp;   • Config/Compartir: /gafas/@alias/?product=prod:ID\&snap=snap:ISO\&state\_b64=… (@alias opcional, estético; los datos válidos están en query).

# &nbsp;   • Checkout: /gafas/@alias//checkout?sku=\&sig=\&snap=snap:ISO\&qty=1 (backend valida solo sku, sig, snap).

# 9\) Integraciones (APIs)

# &nbsp;   • Lectura: GET /catalog/snapshot/latest… (o por ver:) → construye UI de pasos, chips y grupos por slot.

# &nbsp;   • Add to Cart: POST /validate-sign → maneja ok:true|false con Zero‑mensajes (autocorrección + retry 1).

# &nbsp;   • Verify (checkout): POST /verify (normalmente backend)

# &nbsp;   • Assets: GLB/miniaturas desde CDN; cache local por sku\_hash+snapshot\_id.

# 10\) Telemetría y A/B

# &nbsp;   • Eventos: ui.ready, ui.select, ui.autocorrect, ui.add\_to\_cart, ui.error, ui.snapshot\_ready.

# &nbsp;   • Dimensiones: producto\_id, pieza\_id, material\_id, modelo\_id, color\_id, textura\_id, acabado\_id, variante\_ab, device, locale, texture\_source, tint\_applied, slot\_name y, por control, control\_type, control\_value\_id. Para parejas: pair\_id, pair\_lock (ON/OFF).

# &nbsp;   • KPI: tiempo a configuración, % autocorrecciones, éxito add‑to‑cart, latencias de validate/verify.

# &nbsp;   • A/B: orden Color↔Textura, carrusel vs grilla, Acabado por lado.

# 11\) Errores y Zero‑mensajes

# &nbsp;   • Mapeo a códigos estándar (ej.: E\_INVALID\_ID, E\_RULE\_VIOLATION, E\_TEXTURE\_DEFINES\_COLOR, E\_ENCAJE\_FAILED, E\_TIMEOUT, E\_RATE\_LIMIT, E\_INTERNAL).

# &nbsp;   • Degradación silenciosa: el usuario no ve errores; el flujo autocorrige y reintenta 1 vez; si persiste, reabre configurador con selección ajustada.

# 12\) Rendimiento

# &nbsp;   • SLO: LCP/TTI ≤ 2.5 s (4G realista); primer frame < 200 ms.

# &nbsp;   • Budgets: GLB por pieza ≤ 2–3 MB; texturas 256–2048 px.

# &nbsp;   • Precarga Draco/Meshopt; lazy‑load de etapas y assets; limitar concurrencia.

# &nbsp;   • Instancia única del visor/cargador 3D por página (evitar múltiples Three).

# 13\) Seguridad y privacidad

# &nbsp;   • Sin PII en state ni en snapshot.

# &nbsp;   • CSP estricta; CORS según Validate \& Sign.

# &nbsp;   • alias en la URL es solo estético; no afecta validación.

# 14\) Observabilidad

# &nbsp;   • Propagar request\_id desde Validate \& Sign en eventos.

# &nbsp;   • Panel de UI con KPIs y distribución por reason\_key en autocorrecciones.

# 15\) QA \& Checklists

# &nbsp;   • Front: stepper/chips ARIA; computeAllowed; autocorrección; undo/redo; telemetría; Intl.

# &nbsp;   • Visor: gestos; cross‑fade; fallback estático; prefetch Draco.

# &nbsp;   • Contenido/i18n: solo label\_key (no IDs crudos); fallbacks correctos.

# &nbsp;   • Rendimiento: presupuestos cumplidos; memoria estable en dispositivos objetivo.

# 16\) Estilo visual

# &nbsp;   • Tipografía 14–24 px; grid de 8 px; radios 12–16 px; sombras suaves.

# &nbsp;   • Temas Light/Dark; estados hover/focus/active coherentes.

# 17\) Casos límite y resiliencia

# &nbsp;   • Snapshot no cargado: skeleton + retry con backoff.

# &nbsp;   • Asset ausente: mantener último frame.

# &nbsp;   • Verify expirado: reconstrucción silenciosa desde snapshot vigente.

# 18\) Packaging

# &nbsp;   • gafas3d-wizard-modal-vX.Y.Z.zip con README, CHANGELOG, MANIFEST y prueba verify.ui.\* (smoke test de accesibilidad/performance).

# 

# Con esto, el Wizard Modal queda especificado para operar totalmente guiado por snapshot, con slots abiertos, UX accesible y Zero‑mensajes de extremo a extremo.

