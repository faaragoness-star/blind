Capa 2 — Esquemas de datos completos (contrato entre plugins)

⚠️ Cambio clave (alineado a tu instrucción)

• Se eliminan todas las referencias a slots fijos (base, metal\_detail, lens). • Nueva regla: “Los slots son abiertos. El GLB puede traer cualquier slot embebido con cualquier nombre.” • Aclaración: los slots son identificadores técnicos del GLB; su uso real depende de la configuración en el Admin. • Regla editorial: “Desde el admin/editorial se decide cómo se categoriza cada slot embebido: puede comportarse como Color, como Material, como Textura o como Acabado.”

Convenciones base

IDs editoriales con prefijos (prod|pieza|modelo|mat|col|tex|fin|morph|sku).

IDs de publicación: snap: (snapshot), ver: (versión publicada).

Labels i18n: claves estables, no IDs en UI.

Medidas en mm; campos \*\_mm.

Color HEX #RRGGBB (uppercase).

Slots: abiertos (el GLB embebe nombres de slot arbitrarios; no existe una lista fija).

Lado: l|r|n. Variante: R|U.

schema\_version obligatorio.

Zero-mensajes en front: sin chips deshabilitados.

Snapshot publicado = objeto inmutable, ref. externa snapshot\_id.

Entidades editoriales 1.1 Producto: id, label\_key, piezas. 1.2 Pieza: id, label\_key, order. 1.3 Modelo: binding con GLB (source, props, object\_name\_pattern), morph\_capabilities, morph\_aliases. 1.4 Material: id, label\_key, defaults color/texture, safety. 1.5 Color: id, label\_key, hex, source, tint\_rules. 1.6 Textura: embebida (defines\_color=true, maps) o generada (generator\_type, params, tintable en appearance.color\_mode). Campo slot: string libre (no enum) que copia el nombre del slot embebido en el GLB. 1.7 Acabado: id, label\_key. 1.8 Morph: id, type, analytics\_key.

Metadatos de modelo (solo lectura) • slots\_detectados: string\[] — lista de nombres de slots embebidos en el GLB del modelo, usada por Admin para exponer controles por pieza/slot.

Reglas editoriales

• Material → Modelos por pieza.

• Material → Colores.

• Material → Texturas.

• Defaults por material.

• Si tex.defines\_color=true → Color oculto mientras activa.

• Slots abiertos: en Admin se definen controles por slot (material/color/textura/acabado/shader\_params) con defaults y, opcionalmente, affects\_sku:true|false por control; el slot es el dónde y los controles el qué en UI/flujo.

Encaje y morphs

encaje\_policy: driver, target (lug/socket), clearance\_por\_material\_mm, max\_k, safety (espesor, radios).

Morphs por modelo: id, range\_norm, maps\_to.

SKU determinista: morphs derivados de snapshot+encaje.

Snapshot publicado

id: snap:YYYY-MM-DDThh:mm:ssZ.

schema\_version, producto\_id, published\_at, locales.

sku\_policy: include\_morphs\_in\_sku (false por defecto).

entities: piezas, modelos, materiales, colores, texturas, acabados.

rules: material\_to\_modelos, material\_to\_colores, material\_to\_texturas, defaults, encaje, morph\_rules, slot\_mapping\_editorial (controles por slot).

Estado cliente y SKU

state: producto\_id, piezas\[], morphs{}.

Serialización canónica pieza→mat→modelo→col→tex→fin. Si una pieza tiene controles por slot marcados affects\_sku:true, se incluyen dentro de la pieza en un bloque explícito de controles por slot.

morphs no viajan por defecto; opcional incluir en SKU.

Acciones y eventos

Acciones: set\_material, set\_modelo, set\_color, set\_textura, set\_acabado, set\_morph, undo, redo, snapshot\_request.

Eventos visor: ui.ready, ui.applied, ui.metrics, ui.snapshot\_ready, ui.error.

Códigos estándar: E\_INVALID\_ID, E\_RULE\_VIOLATION, E\_TEXTURE\_DEFINES\_COLOR, E\_ASSET\_MISSING, E\_MORPH\_RANGE, E\_TIMEOUT, E\_UNSUPPORTED\_FEATURE.

i18n

Bundles por locale.

Resumen plantilla: {{pieza}} · {{material}} — {{color}} · {{textura}} · {{acabado}}.

Fallback idioma actual → base → id capitalizado.

Validador editorial

IDs únicos y válidos.

pieza.order consistente.

Binding con source+props.

Reglas y defaults completos.

Texturas embebidas con glb\_material, generadas con generator\_type.

encaje\_policy completo.

slot (string) presente cuando aplique; no validar contra enum.

Warning: slot configurado en Admin que no exista en slots\_detectados del modelo.

Versión de catálogo

ver:YYYY-MM-DDThh:mm:ssZ, schema\_version, snapshot\_id, notes, published\_by, published\_at.

Analytics

Eventos ui.\* canónicos.

Dimensiones: producto\_id, pieza\_id, material\_id, modelo\_id, color\_id, textura\_id, acabado\_id, variante\_ab, device, locale, texture\_source, tint\_applied, slot\_name (cuando corresponda) y, por control, control\_type (material/color/textura/acabado) y control\_value\_id.

Métricas: embudo, top selecciones, latencias, % embedded vs generated.

Checklists

Catálogo: IDs, i18n, binding, matrices, defaults, encaje, slot\_mapping\_editorial (controles por slot).

Snapshot: schema\_version, entidades completas, reglas correctas.

Front: solo válidos, serialización estable, defines\_color oculta color, snapshot JPG correcto.

Notas finales

Pieza/Modelo como dominio oficial.

Binding GLB con props.

Texturas: embebidas definen color, generadas tintables via appearance.color\_mode.

Encaje parametrizado, morphs opcionales.

Slots: abiertos y definidos por el GLB; su comportamiento se decide en Admin.

SKU discretos (pieza/mat/modelo/col/tex/fin), sin morphs por defecto.

Telemetría ui.\* unificada.

Privacidad / Routing: El alias de usuario y los slugs de ruta no forman parte de state ni de snapshot; solo afectan routing/UI.

Publicación con snap:/ver: para objetos no editoriales.

