Capa 1 — Identificadores y Naming (versión ACTUALIZADA — Slots abiertos)

Objetivo

Garantizar consistencia entre catálogo, visor, flujo de chips, carrito y analítica.

IDs = claves estables para máquinas; Labels = textos vía i18n para humanos.

Serialización canónica del estado para SKU.

Reglas generales de IDs

Slug interno regex: {3,48}$.

ID completo entre plugins: ^(prod|pieza|modelo|mat|col|tex|fin|morph|sku):\[a-z0-9-]{3,48}$.

Inmutables, únicos, opacos (sin medidas/fechas/idioma).

Nunca se muestran al usuario.

Prefijo opcionalmente omitido internamente si el campo ya lo implica.

Prefijos de tipo

prod: (producto), pieza:, modelo:, mat:, col:, tex:, fin:, morph:, sku:.

Ej.: prod:rx-classic, pieza:moldura, modelo:fr-m1, mat:acetato, col:negro, tex:acetato-base, fin:clearcoat-high, morph:socket-w.

Regla: no reutilizar mismo slug con distintos prefijos.

IDs de publicación

snap: = snapshot publicado, ver: = versión publicada.

No sujetos al regex de IDs editoriales.

Convenciones específicas

Lado L/R en modelo (-l/-r), no en pieza.

Variante R/U en campo variant del modelo.

Familias: sufijos semánticos (-m1, -p2, etc.).

Labels (i18n)

label\_key estable; traducciones en bundles por locale.

Ejemplo clave: modelo.tp-p2-l.name.

Plantilla resumen: {{pieza}} · {{material}} — {{color}} · {{textura}} · {{acabado}}.

Fallbacks seguros: idioma actual → base → id capitalizado.

Namespaces reservados: producto., pieza., modelo., material., color., textura., acabado.\*.

Campos mínimos por entidad

Producto: id, label\_key.

Pieza: id, label\_key.

Modelo: id, pieza\_id, label\_key, variant (R/U), side (l/r/n).

Material: id, label\_key, defaults.

Color: id, label\_key, hex #RRGGBB uppercase.

Textura: id, label\_key, defines\_color, slot: string libre (nombre embebido del GLB).

Acabado: id, label\_key.

Morph: id, type (geometrico/correctivo), analytics\_key opcional.

Medidas

socket\_width\_mm, socket\_height\_mm (moldura).

lug\_width\_mm, lug\_height\_mm (patilla).

tol\_w\_mm, tol\_h\_mm (tolerancias).

Binding al GLB

binding.source.file\_name, object\_name/object\_name\_pattern, model\_code.

props: medidas en mm.

Resolución: preferencia por props, fallback por regex.

Mantener ejes y escala coherentes (Z↑, mm).

Slots dentro de una pieza

• Un objeto GLB asociado a un modelo de una pieza puede traer uno o varios slots (superficies técnicas). • Si una pieza tiene >1 slot embebido, esa pieza tiene subzonas que se personalizan por separado desde Admin. • Los nombres de slot son técnicos (el dónde); la identidad visible la dan los controles definidos en Admin (el qué).

Slots — definición ACTUALIZADA

• Slots abiertos. El GLB puede traer cualquier slot embebido con cualquier nombre.

• Los slots son identificadores técnicos del GLB; su uso real depende del Admin/editorial.

• En Admin se definen controles por slot (material/color/textura/acabado/shader\_params) y sus defaults. El slot es el dónde; los controles definen el qué. Opcionalmente, cada control puede marcarse affects\_sku:true|false según afecte al SKU.

Morphs (naming)

Tipos: geometrico (POSITION), correctivo (NORMAL/TANGENT).

IDs sugeridos: morph:socket-w, morph:socket-h, morph:corner-radius.

Rango y clearances en capas superiores.

Serialización del Estado/SKU

Bloques por pieza, claves pieza→mat→modelo→col→tex→fin.

Orden de piezas editorial fijo.

Morphs no viajan en SKU (derivables de snapshot+encaje).

Modo avanzado: incluir morphs normalizados.

Versionado

schema\_version en payloads (SemVer).

Cambios incompatibles ⇒ major.

Palabras reservadas

No usar: default, all, none, true, false, null, na, r, u, l.

Deprecación

deprecated: true, superseded\_by: .

Mantener mientras existan SKUs vigentes.

Buenas prácticas

IDs cortos, claros, consistentes.

No duplicar significado entre ID y label.

No incluir medidas/temporada en IDs.

Migración términos

part → pieza, form → modelo.

QA: scripts bloquean alias legacy.

Checklist rápida

IDs cumplen regex.

Lado solo en modelo.

Labels solo con label\_key.

HEX válido.

Medidas en \*\_mm.

Binding completo.

SKU serializado correctamente.

schema\_version presente.

Palabras reservadas no usadas.

No sustituir assets publicados.

Namespaces i18n correctos.

Apéndices - Reglas de linting (IDs, hex, label\_key, unicidad). - Ejemplo de binding robusto con props y regex.

