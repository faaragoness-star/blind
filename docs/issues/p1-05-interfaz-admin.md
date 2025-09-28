# P1: Interfaz mínima de administración

Referencia: docs/Plugin 1 — ... Informe.md y Capa 4 — Ui_ux Orquestación — Addenda ....md

- [ ] Pantallas/acciones listadas exactamente como en el doc.
- [ ] Textos/etiquetas tomadas del doc (i18n si procede).

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
#     • 4.5 Estado
#         ◦ Borrador → Analizado → Aprobado técnico → Listo para Catálogo.
#     • 4.6 Historial
#         ◦ Versiones del mismo modelo (hashes, diffs técnicos).
#     • 4.7 Export/Sync
#         ◦ Solo de datos técnicos hacia Catalog \& Rules.

Capa 4 — UI/UX, Orquestación, Accesibilidad y Métricas (ACTUALIZADA — Slots abiertos)

Principios de diseño

Zero-mensajes: sin banners ni errores visibles; autocorrección silenciosa.

Determinismo: misma entrada ⇒ mismo estado/sku_hash.

Velocidad: TTI/LCP < 2.5 s; primer frame < 200 ms.

Accesibilidad WCAG 2.2 AA.

Consistencia: Resumen ≡ chips ≡ visor.

Serialización estable: orden fijo pieza→mat→modelo→col→tex→fin.

Slots abiertos (definidos por el GLB): en Admin se definen controles por slot (material/color/textura/acabado/shader_params) con defaults y, opcionalmente, affects_sku:true|false por control. El slot es el dónde; los controles el qué.

Estructura de pantalla Desktop ≥1024px: visor 3D izquierda, chips derecha. Mobile <1024px: visor arriba, pasos en acordeón. Header: título, precio opcional, compartir. Footer fijo: CTA carrito + resumen. Orden de pasos: Pieza → Material → Modelo → Color → Textura → Acabado.

Componentes de UI 2.1 Stepper (tabs ARIA correctos, teclado completo). 2.2 Chips: radiogroup, accesibles, hit-area ≥44 px, overflow con carrusel y modal. 2.2.1 Chips por slot (si la pieza tiene >1 slot): agrupar por slot (título corto); cada grupo como radiogroup independiente con undo/redo y ARIA correcta. 2.3 Visor 3D: gestos, teclado, toolbar mínima, cámara determinista, fallback estático. 2.4 Resumen: plantilla i18n, live region aria-live. 2.5 CTA Carrito: siempre habilitada, valida en silencio. 2.6 Screenshot: JPG 1024×1024, cache por sku_hash.

i18n Labels por label_key, bundles por idioma, fallback seguro. Intl para números/fechas.

Checklists Front: stepper/chips ARIA, computeAllowed, autocorrección, undo/redo, telemetría, Intl. Visor: gestos, cross-fade, fallback, prefetch. Contenido/i18n: labels correctos, sin IDs crudos.
