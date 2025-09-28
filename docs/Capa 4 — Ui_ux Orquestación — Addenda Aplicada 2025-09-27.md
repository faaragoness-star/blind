Capa 4 — UI/UX, Orquestación, Accesibilidad y Métricas (ACTUALIZADA — Slots abiertos)

Principios de diseño

Zero-mensajes: sin banners ni errores visibles; autocorrección silenciosa.

Determinismo: misma entrada ⇒ mismo estado/sku\_hash.

Velocidad: TTI/LCP < 2.5 s; primer frame < 200 ms.

Accesibilidad WCAG 2.2 AA.

Consistencia: Resumen ≡ chips ≡ visor.

Serialización estable: orden fijo pieza→mat→modelo→col→tex→fin.

Slots abiertos (definidos por el GLB): en Admin se definen controles por slot (material/color/textura/acabado/shader\_params) con defaults y, opcionalmente, affects\_sku:true|false por control. El slot es el dónde; los controles el qué.

Estructura de pantalla Desktop ≥1024px: visor 3D izquierda, chips derecha. Mobile <1024px: visor arriba, pasos en acordeón. Header: título, precio opcional, compartir. Footer fijo: CTA carrito + resumen. Orden de pasos: Pieza → Material → Modelo → Color → Textura → Acabado.

Componentes de UI 2.1 Stepper (tabs ARIA correctos, teclado completo). 2.2 Chips: radiogroup, accesibles, hit-area ≥44 px, overflow con carrusel y modal. 2.2.1 Chips por slot (si la pieza tiene >1 slot): agrupar por slot (título corto); cada grupo como radiogroup independiente con undo/redo y ARIA correcta. 2.3 Visor 3D: gestos, teclado, toolbar mínima, cámara determinista, fallback estático. 2.4 Resumen: plantilla i18n, live region aria-live. 2.5 CTA Carrito: siempre habilitada, valida en silencio. 2.6 Screenshot: JPG 1024×1024, cache por sku\_hash.

Orquestación del flujo Estado cliente JSON con pieza activa, props, morphs. Motor de filtrado: matrices, encaje, safety, reglas de textura. Autoselección: último válido → default → primer válido. Undo/Redo: chips, 20 acciones, Ctrl/Cmd+Z. Add to Cart: serialización canónica, validate-sign, autocorrección silenciosa. Si una pieza expone N slots, el paso muestra N grupos de chips (uno por slot) con reglas y autoselección independientes.

Rutas y parámetros (URLs)

Config (editable / compartir): /gafas/@alias/?product=prod:ID\&snap=snap:ISO\&state\_b64= @alias es opcional (estético, solo path). Los datos válidos están en query.

Checkout / compra (SKU final): /gafas/@alias//checkout?sku=\&sig=\&snap=snap:ISO\&qty=1 El backend valida solo sku, sig, snap (el alias no afecta).

Nombre público del diseño: solo aparece post-checkout (no condiciona ni SKU ni catálogo).

Micro-interacciones Animaciones cortas (150–400 ms), skeletons accesibles, prefers-reduced-motion respetado.

Accesibilidad Contraste ≥4.5:1, foco visible, teclado completo, roles/ARIA correctos, modales accesibles.

Rendimiento y assets TTI/LCP <2.5s, GLB ≤2–3MB, texturas 256–2048 px. Carga diferida/prefetch. Cache sku\_hash+snapshot\_id. Draco/Meshopt precalentados.

i18n Labels por label\_key, bundles por idioma, fallback seguro. Intl para números/fechas.

Telemetría y A/B Eventos ui.\* (ready, select, autocorrect, add\_to\_cart, etc.). Dimensiones: producto\_id, pieza\_id, material\_id, modelo\_id, color\_id, textura\_id, acabado\_id, variante\_ab, device, locale, texture\_source, tint\_applied, slot\_name y, por control, control\_type (material/color/textura/acabado) y control\_value\_id. KPI: tiempo a config, % autocorrecciones, éxito carrito. A/B: Color↔Textura, carrusel vs grilla.

Estados límite y resiliencia Snapshot no cargado: skeleton+retry. Asset ausente: mantener frame. Timeout: retry/backoff. Verify expirado: reconstrucción silenciosa.

Diseño visual Tipografía 14–24 px, grid 8 px, radios 12–16 px, sombras, temas Light/Dark, estados coherentes.

Contratos de datos Config paso, evento selección, evento visor (ui.\*).

QA / Pruebas Flujo completo desktop/móvil, autocorrecciones, undo/redo, add to cart silencioso, snapshot consistente, accesibilidad, rendimiento, correlación con request\_id.

Checklists Front: stepper/chips ARIA, computeAllowed, autocorrección, undo/redo, telemetría, Intl. Visor: gestos, cross-fade, fallback, prefetch. Contenido/i18n: labels correctos, sin IDs crudos.

Pseudocódigo Definición de onChange, computeAllowed, addToCart con autocorrección y trackSuccess.

Mapeo de errores Registro con code estándar + reason\_key. Front mantiene Zero-mensajes.

Notas finales Aria-selected en tabs, reason\_key unificado, caso lente no tintable salvo override, TTL 90 días screenshots, A/B controlado, correlación completa (idempotency\_key + request\_id), optimización listas.



Addenda aplicada — 2025-09-27 (sin ejemplos)

Selector de Pareja (Patillas)

&nbsp;   • Un grupo de chips por pareja (pair\_id) en lugar de dos listas L/R.

&nbsp;   • Toggle “Acabado por lado”:

&nbsp;       ◦ OFF: un acabado único aplica a ambas.

&nbsp;       ◦ ON: aparecen “Acabado (Izq.)” y “Acabado (Dcha.)”.

Orquestación de parejas

&nbsp;   • Al seleccionar una pareja: asignación atómica de left\_model\_id y right\_model\_id a patilla\_L/patilla\_R.

&nbsp;   • Sincronizar automáticamente material, color, textura.

&nbsp;   • Nunca sincronizar acabado.

Telemetría

&nbsp;   • Añadir dimensiones: pair\_id, pair\_lock (ON/OFF) en eventos ui.select y ui.add\_to\_cart.

