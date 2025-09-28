Plugin 2 — G3D Catalog \& Rules (reglas editoriales, i18n, snapshot/versión)

1\) Identidad

&nbsp;   • Objetivo: convertir los modelos técnicos “listos” en un catálogo editorial con reglas (Material→Modelos/Colores/Texturas), defaults, mapeo de controles por slot y publicación de snapshots inmutables (snap:) con versión de catálogo (ver:).

2\) Visibilidad

&nbsp;   • Front (público): no visible como UI. Expone endpoints/JSON consumidos por Wizard/Viewer.

&nbsp;   • Admin: sí. Panel completo para reglas, i18n, defaults, mapeo de slots y publicación.

3\) Alcance y límites

&nbsp;   • ✅ Define reglas editoriales y defaults (no geometría).

&nbsp;   • ✅ Gestiona i18n (label\_key → traducciones por locale).

&nbsp;   • ✅ Configura controles por slot (material/color/textura/acabado/shader\_params) y si affects\_sku por control.

&nbsp;   • ✅ Publica snapshot (snap:) y versión de catálogo (ver:), con diff/auditoría.

&nbsp;   • ❌ No valida ni firma SKUs (eso es Validate \& Sign).

&nbsp;   • ❌ No ingesta GLB ni extrae props (eso es Models Manager).

4\) Admin — Estructura / Secciones

&nbsp;   • 4.1 Dashboard: estado del catálogo, borradores vs snapshots, warnings.

&nbsp;   • 4.2 Entidades:

&nbsp;       ◦ Productos: id, label\_key, piezas incluidas.

&nbsp;       ◦ Piezas: orden editorial, visibilidad.

&nbsp;       ◦ Modelos (referencia a Models Manager): selección de g3d\_model ready.

&nbsp;       ◦ Materiales: defaults (color→textura), reglas de seguridad.

&nbsp;       ◦ Colores: HEX #RRGGBB (uppercase), fuente (embebido/tintable).

&nbsp;       ◦ Texturas: embebida (del GLB) o generada (params). Campo slot: string libre (copiado del GLB; sin enum).

&nbsp;       ◦ Acabados: lista discreta.

&nbsp;   • 4.3 Reglas:

&nbsp;       ◦ Matrices Material→Modelos por pieza.

&nbsp;       ◦ Matrices Material→Colores y Material→Texturas.

&nbsp;       ◦ Defaults por material (y por pieza/slot si aplica).

&nbsp;       ◦ Encaje (clearances por material; safety min\_thickness/radius por pieza).

&nbsp;   • 4.4 Slots (mapeo editorial):

&nbsp;       ◦ Detecta slots\_detectados\[] provenientes del modelo seleccionado.

&nbsp;       ◦ Permite activar/desactivar un slot en UI.

&nbsp;       ◦ Define por slot: controles (material/color/textura/acabado/shader\_params), defaults, orden/visibilidad y affects\_sku:true|false por control.

&nbsp;   • 4.5 i18n:

&nbsp;       ◦ Bundles por idioma (import/export CSV/JSON), validación de claves huérfanas.

&nbsp;   • 4.6 Previsualización:

&nbsp;       ◦ Sandbox con snapshot de trabajo, aplica Zero‑mensajes.

&nbsp;   • 4.7 Publicación:

&nbsp;       ◦ Validador editorial → Diff → Crear snapshot (snap:) → Crear versión de catálogo (ver:).

&nbsp;       ◦ Rollback a snapshot anterior.

&nbsp;   • 4.8 Auditoría: quién/cuándo/qué; diffs por campo, request\_id.

5\) Modelo de datos (resumen)

&nbsp;   • CatalogDraft (editable):

{

&nbsp; "schema\_version": "2.0.0",

&nbsp; "producto\_id": "prod:base",

&nbsp; "piezas": \[{"id":"pieza:frame","order":1},{"id":"pieza:temple","order":2}],

&nbsp; "modelos": \[{"id":"modelo:FR\_A\_R","g3d\_model\_id":"…","slots\_detectados":\["MAT\_BASE","MAT\_TIP"]}],

&nbsp; "materiales": \[{"id":"mat:acetato","defaults":{"color":"col:black","textura":"tex:acetato\_base"}}],

&nbsp; "colores": \[{"id":"col:black","hex":"#000000"}],

&nbsp; "texturas": \[{"id":"tex:acetato\_base","slot":"MAT\_BASE","defines\_color":true,"source":"embedded"}],

&nbsp; "acabados": \[{"id":"fin:clearcoat\_high"}],

&nbsp; "reglas": {

&nbsp;   "material\_to\_modelos": {"pieza:frame":{"mat:acetato":\["modelo:FR\_A\_R"]}},

&nbsp;   "material\_to\_colores": {"mat:acetato":\["col:black","col:white"]},

&nbsp;   "material\_to\_texturas": {"mat:acetato":\["tex:acetato\_base"]},

&nbsp;   "defaults": {"mat:acetato":{"color":"col:black","textura":"tex:acetato\_base"}},

&nbsp;   "encaje": {"clearance\_por\_material\_mm":{"mat:acetato":0.10}},

&nbsp;   "slot\_mapping\_editorial": {

&nbsp;     "pieza:frame": {

&nbsp;       "MAT\_BASE": {

&nbsp;         "controles": \[

&nbsp;           {"type":"material","affects\_sku":true},

&nbsp;           {"type":"color","affects\_sku":true},

&nbsp;           {"type":"textura","affects\_sku":true},

&nbsp;           {"type":"acabado","affects\_sku":false}

&nbsp;         ],

&nbsp;         "defaults": {"material":"mat:acetato","color":"col:black","textura":"tex:acetato\_base"},

&nbsp;         "visible": true,

&nbsp;         "order": 1

&nbsp;       }

&nbsp;     }

&nbsp;   }

&nbsp; }

}

&nbsp;   • Snapshot (publicado, inmutable):

{

&nbsp; "id": "snap:2025-09-27T18:45:00Z",

&nbsp; "schema\_version": "2.0.0",

&nbsp; "producto\_id": "prod:base",

&nbsp; "entities": {"piezas":…, "modelos":…, "materiales":…, "colores":…, "texturas":…, "acabados":…},

&nbsp; "rules": {…},

&nbsp; "published\_at": "2025-09-27T18:45:00Z",

&nbsp; "published\_by": "user:admin",

&nbsp; "notes": "v2 — slots abiertos",

&nbsp; "ver": "ver:2025-09-27T18:45:00Z"

}

6\) APIs / Contratos (lectura)

&nbsp;   • GET /catalog/snapshot/latest?producto\_id=prod:base\&locale=es-ES → Snapshot vigente (filtrado por producto/locale si aplica).

&nbsp;   • GET /catalog/version/{ver} → versión de catálogo.

&nbsp;   • GET /catalog/draft (admin) → catálogo en edición.

&nbsp;   • Webhooks: catalog.published (payload: snapshot\_id, ver, diff\_summary).

Regla: slots abiertos; slot en Textura es string libre. El front no valida contra listas fijas.

7\) RBAC

&nbsp;   • Editor: editar entidades y reglas; no publica.

&nbsp;   • QA/Revisor: ejecutar Validador editorial, marcar “Listo”.

&nbsp;   • Publicador: crear snapshot y versión; ejecutar rollback.

&nbsp;   • Admin: parámetros globales (idiomas, límites, CORS, backups).

8\) Flujos críticos

&nbsp;   1. Armar catálogo (entidades + reglas + defaults + mapeo de slots) → Guardar borrador.

&nbsp;   2. Validar (IDs, referencias, props, matrices, slots existentes, i18n completo, defines\_color vs color visible).

&nbsp;   3. Previsualizar (sandbox con Zero‑mensajes).

&nbsp;   4. Publicar (snapshot + ver) → dispara webhooks.

&nbsp;   5. Rollback (seleccionar snapshot anterior) si hay regresión.

9\) Errores \& Zero‑mensajes

&nbsp;   • Bloqueantes: E\_INVALID\_ID, E\_REF\_MISSING, E\_RULE\_VIOLATION, E\_TEXTURE\_DEFINES\_COLOR, E\_SLOT\_NOT\_FOUND\_IN\_MODEL.

&nbsp;   • Avisos: i18n incompleto, GLB pesado, defaults ausentes.

&nbsp;   • Front recibe solo el snapshot válido; los errores quedan en Admin/logs.

10\) Rendimiento

&nbsp;   • Snapshot JSON comprimido ≤ 200–400 KB típico.

&nbsp;   • CDN + cache ETag/Cache-Control; invalidación controlada en publicación.

11\) Observabilidad

&nbsp;   • Logs de publicación con request\_id y diff.

&nbsp;   • Métricas: tamaño snapshot, tiempos de validación, nº slots por pieza, % texturas embebidas vs generadas.

12\) Seguridad

&nbsp;   • Admin con SSO/2FA, RBAC estricto.

&nbsp;   • CORS de lectura hacia dominios permitidos.

&nbsp;   • Sin PII en snapshot; alias de usuario no forma parte del snapshot.

13\) Versionado \& Packaging

&nbsp;   • g3d-catalog-rules-vX.Y.Z.zip (README/CHANGELOG/MANIFEST, scripts verify.\*).

&nbsp;   • SemVer en schema\_version. Compatibilidad N/N-1.

14\) Backups \& DR

&nbsp;   • Backup de draft, snapshots y versiones; retención 90 días.

&nbsp;   • RPO ≤ 24 h; RTO ≤ 4 h.

15\) QA \& Checklists

&nbsp;   • Antes de publicar: IDs válidos, i18n base completo, matrices consistentes, slot\_mapping\_editorial sin huérfanos, defaults aplicables.

&nbsp;   • Después de publicar: snapshot accesible, CDN invalidado, webhook entregado, Wizard consume sin warnings.

16\) Extensiones (parejas L/R)

&nbsp;   • Pair controls para patillas: pair\_id, sync\_controls=\[material,color,textura], unsynced\_controls=\[acabado].

&nbsp;   • Validación: left\_model\_id.side == L y right\_model\_id.side == R.



Con esto, Catalog \& Rules queda especificado para operar con slots abiertos, snapshots reproducibles e integración limpia con Wizard y Validate \& Sign.

