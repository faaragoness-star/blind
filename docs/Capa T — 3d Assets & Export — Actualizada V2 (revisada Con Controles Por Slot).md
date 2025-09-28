Capa T — 3D Assets \& Export (ACTUALIZADA — Slots abiertos)

🧭 Reglas rápidas

Encaje = Y + W (mm) de la raíz/“socket”.

Y = altura de la raíz (vertical).

W = ancho de la raíz (horizontal en vista Front).

Los nombres llevan Y-W redondeados al mm para leer rápido. Las propiedades guardan los mm exactos.

FRAMED = con montura (socket). RIMLESS = sin montura (atornillada al lente).

Monturas FRAMED tienen dos variantes: \_R realista (socket modelado) · \_U universal (borde plano).

Los materiales y slots del GLB son abiertos: nómbralos de forma estable (p. ej., MAT\_\*). No existe una lista fija.

🔣 Códigos

FR = Frame (montura)

TP = Temple (patilla)

RM = Rimless Mount (pieza de sujeción en rimless)

Lado: L izquierda · R derecha

Variante montura: R realista · U universal

Modelo: letra/código libre (A, B, C…)

Unidades: milímetros (mm)

🏷️ Convención de nombres (OBJETO — triángulo naranja)

Formato: primero Y, luego W, ambos redondeados al mm.

Montura FRAMED realista: FR{Modelo}\_{Y}-{W}\_R ej. FRA\_12-6\_R

Montura FRAMED universal: FR{Modelo}\_U ej. FRA\_U

Patilla: TP{Modelo}{Y}-{W}{L|R} ej. TPA\_12-6\_L, TPA\_12-6\_R

Rimless (pieza de sujeción): RM{Modelo} ej. RMA

Patilla rimless: TP{Modelo}R{L|R} ej. TPK\_R\_L

Las colecciones son solo para ordenar; lo importante está en el objeto.

🧾 Propiedades (Custom Properties) — en el OBJETO

(Objeto → Object Properties → Custom Properties → Add)

Comunes

model\_code → “A” (tu código de modelo)

Patillas FRAMED

lug\_height\_mm → Y exacto (ej. 11.7)

lug\_width\_mm → W exacto (ej. 6.25)

side → “L” / “R”

(opcional) tol\_h\_mm = 0.7 · tol\_w\_mm = 0.4

Monturas FRAMED

mount\_type → “FRAMED”

variant → “R” o “U”

(solo realista)

socket\_height\_mm → Y exacto

socket\_width\_mm → W exacto

(opcional) tol\_h\_mm = 0.7 · tol\_w\_mm = 0.4

Rimless (RM)

mount\_type → “RIMLESS”

(opcional futuro) rimless\_family, hole\_spacing\_mm, hole\_diameter\_mm

Patillas Rimless

fit → “R”

side → “L” / “R”

(opcional) rimless\_family

Materiales y slots (nota editorial)

• Los slots son abiertos. El GLB puede traer cualquier slot embebido con cualquier nombre (p. ej., MAT\_BASE, MAT\_TIP, MAT\_LENS, MAT\_XYZ…).

• Los slots son identificadores técnicos del GLB; su uso real depende del Admin/editorial.

• En Admin se definen controles por slot (material/color/textura/acabado/shader\_params) con defaults y, opcionalmente, affects\_sku:true|false por control. El slot es el dónde; los controles definen el qué.

🧱 Plantillas para rellenar

Montura FRAMED realista

\[OBJETO]

Nombre: FR{Modelo}\_{Yredondeado}-{Wredondeado}\_R

model\_code: “{Modelo}”

mount\_type: “FRAMED”

variant: “R”

socket\_height\_mm: {Y exacto}

socket\_width\_mm: {W exacto}

tol\_h\_mm: 0.7

tol\_w\_mm: 0.4

Montura FRAMED universal

\[OBJETO]

Nombre: FR{Modelo}\_U

model\_code: “{Modelo}”

mount\_type: “FRAMED”

variant: “U”

Patilla FRAMED (izq./dcha.)

\[OBJETO]

Nombre: TP{Modelo}{Yredondeado}-{Wredondeado}{L|R}

model\_code: “{Modelo}”

lug\_height\_mm: {Y exacto}

lug\_width\_mm: {W exacto}

side: “{L|R}”

tol\_h\_mm: 0.7

tol\_w\_mm: 0.4

Rimless – pieza de sujeción (lugs/bridge)

\[OBJETO]

Nombre: RM{Modelo}

model\_code: “{Modelo}”

mount\_type: “RIMLESS”

Patilla Rimless (izq./dcha.)

\[OBJETO]

Nombre: TP{Modelo}R{L|R}

model\_code: “{Modelo}”

fit: “R”

side: “{L|R}”

✅ Checklist de exportación

Escala normalizada (montura X ≈ 0.145 m), Ctrl+A → All Transforms.

Pivotes:

Montura → puente (idealmente 0,0,0).

Patillas → centro del eje de su bisagra.

Nombres según convención Y–W.

Custom Properties añadidas (Y/W exactos y tolerancias).

Slots abiertos en materiales: usa nombres estables (MAT\_\*), evita dependencias de listas fijas.

En glTF: incluir custom properties activado.

🧪 Ejemplo relleno (rápido)

Patilla A izquierda, medida Y=11.7 mm, W=6.25 mm

Nombre: TPA\_12-6\_L

Props: lug\_height\_mm=11.7, lug\_width\_mm=6.25, side=“L”, tol\_h\_mm=0.7, tol\_w\_mm=0.4

Montura A realista con socket Y=12.0, W=6.1

Nombre: FRA\_12-6\_R

Props: socket\_height\_mm=12.0, socket\_width\_mm=6.1, variant=“R”, mount\_type=“FRAMED”

Regla de compatibilidad (en tu web) Usa la realista si: |lug\_width\_mm - socket\_width\_mm| ≤ tol\_w\_mm y |lug\_height\_mm - socket\_height\_mm| ≤ tol\_h\_mm; si no, usa ``.

Anexo — Blueprint completo — “Raíz de la moldura” paramétrica (morph targets) que se adapta al tamaño de la patilla — ORIGINAL (sin cambios)

¡Hecho! Aquí tienes el blueprint completo para construir una RAÍZ de moldura paramétrica (morph targets) que se adapta al tamaño de la patilla sin artefactos, con nombres, pasos y checks listos para producción.

Meta-objetivo

La montura contiene una zona convertible (“RAÍZ / socket”) que morfamos en ancho (W) y alto (H) para igualar el LUG de la patilla.

El visor aplica los morphs leyendo medidas (mm) desde userData/extras; no reescalamos toda la montura.

Estándar de escena (Blender)

Unidades: Metric, Unit Scale = 1.0 (1 unidad = 1 m). mm = 0.001 m.

Ejes: Z↑ (arriba), Y hacia cámara. (Blender exporta a glTF correctamente.)

Origen (punto naranja) de la montura: centro del puente, parte inferior.

Colecciones:

Montura → objeto \*FRA\_ (frame)

Patilla\_izquierda → TP\*\_L

Patilla\_derecha → TP\*\_R

Empties (anclas) (nombres exactos):

Frame\_Anchor (en el origen del proyecto)

Temple\_L\_Anchor y Temple\_R\_Anchor (eje de bisagras)

Socket\_Cage (empty hijo de la RAÍZ; sirve de referencia local de la zona convertible)

Propiedades (custom properties)

Rellénalas en Object → Properties → Custom Properties (ya lo haces):

Montura (FRA\_\*)

model\_code : “A”, “B”, …

mount\_type : “FRAMED” | …

socket\_width\_mm : (base de la RAÍZ)

socket\_height\_mm : (base de la RAÍZ)

\*Patillas (TP\*\*)\*

model\_code : “A”, “B”, …

side : “L” | “R”

lug\_width\_mm : (ancho del LUG)

lug\_height\_mm : (alto del LUG)

Nota sobre variant: úsalo opcionalmente como sufijo (“R”, “L”, “Wide”, “Slim”) si quieres distinguir sub-versiones estéticas. El flujo no lo necesita.

Modelado de la RAÍZ (moldura)

Topología:

Crea un bucle de aristas (o dos) que rodee la zona de unión.

2–3 support loops para sostener el perfil cuando el morph estire/estreche.

Evita n-gons; quads preferibles.

Vertex Group: selecciona los vértices de la zona convertible y crea VG\_socket. Te ayudará con Lattice/Corrective y para aislar morphs.

Transiciones: añade un fillet suave hacia el aro (otro ring) para que el morph no “rompa” el sombreado.

UV:

Madera → Triplanar (o box mapping) en la RAÍZ para que la veta no se estire con el morph.

Acetato/metal → UV simple o procedural; si ves estiramientos, cambia a triplanar en esa zona.

Modelado del LUG (patilla)

Bloque sólido y medido: caras planas en X/Y, con chaflán mínimo si procede.

Guarda lug\_width\_mm y lug\_height\_mm.

El ancla de la patilla se alinea a Temple\_\*\_Anchor, no al origen.

Morph targets (shape keys) de la RAÍZ

Nombre del objeto de RAÍZ: puede ser la propia montura si la RAÍZ está integrada (FRA\_\*), o un sub-objeto si lo separas. Las shape keys viven en ese objeto.

Shape Keys (nombres exactos)

Basis

Ancho: SK\_W\_Narrow, SK\_W\_Wide

Alto: SK\_H\_Low, SK\_H\_High

Acabado: SK\_Corner\_Radius (redondeo de esquinas)

Transición: SK\_Fillet\_Blending (suaviza el encuentro con el aro)

Rango de diseño por defecto Monta las shape keys para un rango ±25 % respecto a socket\_\*\_mm base. (Si más tarde necesitas ±30 %, amplías sin cambiar nombres.)

Cómo construir las shape keys (limpio y rápido)

Prepara Lattice (recomendado para extremos limpios)

Añade un Lattice que envuelva VG\_socket.

Lattice con divisiones 3–5 en X y 3–4 en Z (según complejidad).

Añade Lattice Modifier al objeto RAÍZ con Vertex Group = VG\_socket.

Moldea extremos

Para SK\_W\_Wide: en Edit Mode del Lattice, ensancha controladamente la zona (usa vistas ortográficas y medidas).

Para SK\_W\_Narrow: copia del lattice, ahora estrecha.

Igual para SK\_H\_High / SK\_H\_Low (alto).

Aplica como Shape Key

En el Lattice Modifier del objeto RAÍZ, menú ▼ Apply as Shape Key (Blender 4.x).

Crea SK\_W\_Wide, SK\_W\_Narrow, etc., uno por extremo.

Borra o desactiva el Lattice; ya quedaron los morphs dentro del mesh.

Sculp/ajustes finos

SK\_Corner\_Radius: esculpe el radio (sin mover fuera de la zona).

SK\_Fillet\_Blending: microajuste del borde de transición (0–1 ~ 0–0.5 mm).

Consejo: Si no tienes “Apply as Shape Key” en el modificador, duplica malla deforme → Join as Shapes sobre la original.

Drivers de previsualización (opcionales)

Sirven en Blender para ver cómo reaccionaría; en producción, el visor controla las influencias.

Añade en el objeto RAÍZ dos custom props: preview\_target\_width\_mm, preview\_target\_height\_mm.

Crea drivers en las shape keys:

Influencia SK\_W\_Wide = clamp( (previewW - baseW) / (baseW\*0.25), 0, 1 )

Influencia SK\_W\_Narrow = clamp( (baseW - previewW) / (baseW\*0.25), 0, 1 )

Igual con H\_High/H\_Low para alturas.

SK\_Corner\_Radius: usa max(|kW|, |kH|) \* 0.5 (clamp 0–1).

Base (baseW, baseH) = socket\_width\_mm, socket\_height\_mm (recuerda que están en mm → convierte a m si tu driver mezcla distancias). Para drivers sólo usas proporciones, así evitas errores de unidades.

Tolerancias y límites

Holguras por material (por defecto): madera +0.20 mm, acetato +0.10 mm, metal +0.05 mm. (Se guardan en el catálogo JSON; el visor las aplica.)

Espesor mínimo en la RAÍZ: madera ≥ 2.8 mm, acetato ≥ 2.2 mm, metal ≥ 1.2 mm.

Radio mínimo en esquinas convertibles: ≥ 0.6 mm en madera (ajusta en SK\_Corner\_Radius si el cambio es grande).

Rango de morph: ±25 %. Si una patilla pide más → combos no disponibles o RAÍZ variante (FRA\_\*\_Wide, por ejemplo).

Exportación GLB (glTF 2.0)

Aplica Location/Rotation/Scale (Ctrl+A) en objetos.

Deja las shape keys (no las apliques).

Materiales: usa tus nodos (madera/acetato/metal). Para triplanar, mantén Texturas en UV/box compatibles con glTF.

Exporta cada pieza (FRA\_, TP\_L, TP\_R) por separado:

Formato: glTF Binary (.glb)

Shape Keys: activado

Tangents: activado (si usas normal map)

Apply Modifiers: sólo si los necesitas; evita destruir shape keys

Selected Objects: activado (exporta sólo lo seleccionado)

Catálogo y reglas (JSON)

Archivo: /wp-content/uploads/catalog.json (lo generas con tu gestor; el visor sólo lee).

{ “materials”: \[ {“code”:“wood”, “clearance\_mm”:0.20, “min\_thickness\_mm”:2.8, “allow”:\[“FRA\_”,”TPA\_”], “deny”:\[“TPB\_ultraThin”]}, {“code”:“acetate”,“clearance\_mm”:0.10, “min\_thickness\_mm”:2.2, “allow”:\[“\*”], “deny”:\[]}, {“code”:“metal”, “clearance\_mm”:0.05, “min\_thickness\_mm”:1.2, “allow”:\[“FRB\_”,”TPB\_”], “deny”:\[“TPA\_woodStyle”]} ], “frames”: \[ {“code”:“FRA\_6-3\_R”,“glb”:“/uploads/FRA\_6-3\_R.glb”,“socket\_width\_mm”:3.00,“socket\_height\_mm”:6.00} ], “temples”: \[ {“code”:“TPA\_6-3\_L”,“glb”:“/uploads/TPA\_6-3\_L.glb”,“side”:“L”,“lug\_width\_mm”:3.00,“lug\_height\_mm”:6.00}, {“code”:“TPA\_6-3\_R”,“glb”:“/uploads/TPA\_6-3\_R.glb”,“side”:“R”,“lug\_width\_mm”:3.00,“lug\_height\_mm”:6.00} ] }

En UI, no mostramos las combinaciones que choquen con deny o superen el rango de morph. Simplemente no aparecen.

Lógica de encaje (en el visor)

La montura tiene las shape keys (SK\_\*). El visor ajusta sus influencias leyendo medidas y holguras.

Fórmulas (runtime)

const clearance = clearanceByMaterial\[material]; // p. ej. 0.20 mm

const targetW = lugW + clearance; // mm

const targetH = lugH + clearance; // mm

const kW = clamp( (targetW - baseW) / (baseW \* 0.25), -1, 1 );

const kH = clamp( (targetH - baseH) / (baseW \* 0.25), -1, 1 );

influence\[‘SK\_W\_Wide’] = Math.max(0, kW);

influence\[‘SK\_W\_Narrow’] = Math.max(0, -kW);

influence\[‘SK\_H\_High’] = Math.max(0, kH);

influence\[‘SK\_H\_Low’] = Math.max(0, -kH);

influence\[‘SK\_Corner\_Radius’] = clamp(Math.max(Math.abs(kW), Math.abs(kH))\*0.5, 0, 1);

influence\[‘SK\_Fillet\_Blending’] = 0.25 \* Math.max(influence\[‘SK\_Corner\_Radius’], Math.max(influence\[‘SK\_W\_Wide’], influence\[‘SK\_H\_High’]));

Alineación de patillas

Coloca la patilla de forma que su ancla (un empty exportado o un nodo nombrado) coincida con Temple\_L\_Anchor / Temple\_R\_Anchor de la montura.

Aplica orientación del ancla; luego parenta o constriñe.

QA / Checklist de producción

Geometría

Sombras/UV

Medidas

Exportación

Visor

Escalabilidad / variantes

Si alguna forma extrema exige > ±25 % → crea RAÍZ variante (p. ej. FRA\_6-3\_R\_wide con baseW/baseH diferentes) y deja que el catálogo seleccione automáticamente la mejor base según patilla elegida.

Puedes separar RAÍZ en un sub-objeto si alguna línea futura tiene placas intercambiables (mismo pipeline de morphs).

Resumen rápido de nombres (clave)

Anchors: Frame\_Anchor, Temple\_L\_Anchor, Temple\_R\_Anchor, Socket\_Cage

Vertex Group: VG\_socket

Shape Keys: SK\_W\_Narrow, SK\_W\_Wide, SK\_H\_Low, SK\_H\_High, SK\_Corner\_Radius, SK\_Fillet\_Blending

Props Montura: socket\_width\_mm, socket\_height\_mm, model\_code, mount\_type

Props Patilla: lug\_width\_mm, lug\_height\_mm, side, model\_code

Con esto, tu RAÍZ paramétrica queda lista para fabricación virtual: robusta, medible, sin estirar texturas, y controlada por el visor con cambios instantáneos al elegir patillas o materiales.

¡perfecto! En esa ventana de exportación glTF 2.0 (.glb) te dejo un checklist de arriba a abajo con lo que debes marcar/poner siempre para tus gafas (sin rig ni animaciones) y con Draco:

Checklist — Exportar GLB con Draco (Blender 4.x)

Cabecera

Format: glTF Binary (.glb)

Remember Export Settings: ✅ (opcional, mejor aún: guarda un Preset al final)

Include

Limit to →

Selected Objects: ✅ (si exportas una sola pieza; si exportas todo, déjalo apagado)

El resto: ⛔

Data → Custom Properties, Cameras, Punctual Lights: ⛔

Transform

+Y Up: ✅ (deja el eje por defecto del exportador glTF)

(Nada más aquí; las transformaciones deben estar aplicadas antes: Ctrl+A Location/Rotation/Scale)

Data / Scene Graph (si aparece)

Déjalo por defecto (no forzar colecciones/NLA/etc. para este caso)

Mesh

Apply Modifiers: ✅

UVs / Vertex Colors / Materials: por defecto ✅

Tangents: ✅ (si usas normal maps)

Compress… (esto es aparte, ver “Compression” más abajo)

Material

Export: Principled BSDF (o Export) ✅

No toques Alpha ni Double Sided salvo que lo necesites.

Armature / Skinning / Lighting

Armature: ⛔ (si tus gafas no llevan huesos)

Skinning: ⛔

Lighting: ⛔

Animation

Animations: ⛔ (si no hay animaciones)

«Bake All Actions», «NLA Strips», etc.: ⛔

Images (si aparece)

Images: Automatic

Image Quality: 75 (ok)

Create WebP: ⛔ (no necesario)

Unused Images: ✅ (limpia recursos no usados)

Optimize (si aparece)

Use Sparse Accessor if Better: ✅

Omitting Sparse Accessor if Data is Empty: ✅

Compression ➜ DRACO

Compression (Draco): ✅

Compression Level: 6–7 (equilibrio calidad/peso)

(Si tu Blender muestra cuantizaciones DRACO):

Quantize Position: 14

TexCoord: 10

Normal: 8

Color: 8

Generic: 8

Si no ves cuantizaciones en Blender, no pasa nada: exporta con Level 6–7 y, si quieres ajuste fino, luego pasas el archivo por gltf-transform.

Nombre de archivo y destino

Nombre: FRA\_6-3\_R\_draco.glb (consistente y sin espacios)

Carpeta: /models/v1\_draco/ (versiona tus builds)

Guardar Preset

Arriba a la derecha del diálogo de exportación → menú Presets.

Add Preset → nómbralo GLB\_DRACO\_BLIND.

A partir de ahora, elige ese preset y exportas siempre igual.

Mini-QA tras exportar

En WP/visor, consola:

new THREE.GLTFLoader().load(‘URL\_AL\_GLB’, gltf => { console.log(‘DRACO?’, !!(gltf?.parser?.extensions?.KHR\_draco\_mesh\_compression)); });

Debe salir true.

Comprueba el tamaño del .glb (objetivo típico por pieza: 6–12 MB).

Antes de exportar (recordatorio rápido)

Ctrl+A Apply All Transforms (Loc/Rot/Scale).

Pivotes correctos: montura centrada; patilla con origen en bisagra.

Malla limpia (Merge by Distance, sin loose, Shade Smooth + Auto Smooth).

UV único limpio si usas texturas.

Nombres estables de objetos/materiales (coincidirán con tu UI/API).

Con este checklist tendrás exportaciones consistentes y listas para el visor con Draco. Si quieres, te preparo un preset .json de Blender y te indico dónde colocarlo para cargarlo con un clic.

