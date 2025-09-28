# P1: Binding técnico a slots/Capa T

Referencia: docs/Capa T — 3d Assets & Export — Actualizada V2 (revisada Con Controles Por Slot).md

- [ ] Reglas de slot aplicadas tal cual están.
- [ ] Metadatos requeridos copiados.

Capa T — 3D Assets & Export (ACTUALIZADA — Slots abiertos)

🧭 Reglas rápidas

Encaje = Y + W (mm) de la raíz/“socket”.

Y = altura de la raíz (vertical).

W = ancho de la raíz (horizontal en vista Front).

Los nombres llevan Y-W redondeados al mm para leer rápido. Las propiedades guardan los mm exactos.

FRAMED = con montura (socket). RIMLESS = sin montura (atornillada al lente).

Monturas FRAMED tienen dos variantes: _R realista (socket modelado) · _U universal (borde plano).

Los materiales y slots del GLB son abiertos: nómbralos de forma estable (p. ej., MAT_*). No existe una lista fija.

🔣 Códigos

FR = Frame (montura)

TP = Temple (patilla)

RM = Rimless Mount (pieza de sujeción en rimless)

Lado: L izquierda · R derecha

Variante montura: R realista · U universal

Modelo: letra/código libre (A, B, C…)

Unidades: milímetros (mm)

🧾 Propiedades (Custom Properties) — en el OBJETO

(Objeto → Object Properties → Custom Properties → Add)

Comunes

model_code → “A” (tu código de modelo)

Patillas FRAMED

lug_height_mm → Y exacto (ej. 11.7)

lug_width_mm → W exacto (ej. 6.25)

side → “L” / “R”

(opcional) tol_h_mm = 0.7 · tol_w_mm = 0.4

Monturas FRAMED

mount_type → “FRAMED”

variant → “R” o “U”

(solo realista)

socket_height_mm → Y exacto

socket_width_mm → W exacto

(opcional) tol_h_mm = 0.7 · tol_w_mm = 0.4

Rimless (RM)

mount_type → “RIMLESS”

(opcional futuro) rimless_family, hole_spacing_mm, hole_diameter_mm

Patillas Rimless

fit → “R”

side → “L” / “R”

(opcional) rimless_family

Materiales y slots (nota editorial)

• Los slots son abiertos. El GLB puede traer cualquier slot embebido con cualquier nombre (p. ej., MAT_BASE, MAT_TIP, MAT_LENS, MAT_XYZ…).

• Los slots son identificadores técnicos del GLB; su uso real depende del Admin/editorial.

• En Admin se definen controles por slot (material/color/textura/acabado/shader_params) con defaults y, opcionalmente, affects_sku:true|false por control. El slot es el dónde; los controles definen el qué.

🧱 Plantillas para rellenar

Montura FRAMED realista

[OBJETO]

Nombre: FR{Modelo}_{Yredondeado}-{Wredondeado}_R

model_code: “{Modelo}”

mount_type: “FRAMED”

variant: “R”

socket_height_mm: {Y exacto}

socket_width_mm: {W exacto}

tol_h_mm: 0.7

tol_w_mm: 0.4

Montura FRAMED universal

[OBJETO]

Nombre: FR{Modelo}_U

model_code: “{Modelo}”

mount_type: “FRAMED”

variant: “U”

Patilla FRAMED (izq./dcha.)

[OBJETO]

Nombre: TP{Modelo}{Yredondeado}-{Wredondeado}{L|R}

model_code: “{Modelo}”

lug_height_mm: {Y exacto}

lug_width_mm: {W exacto}

side: “{L|R}”

tol_h_mm: 0.7

tol_w_mm: 0.4

Rimless – pieza de sujeción (lugs/bridge)

[OBJETO]

Nombre: RM{Modelo}

model_code: “{Modelo}”

mount_type: “RIMLESS”

Patilla Rimless (izq./dcha.)

[OBJETO]

Nombre: TP{Modelo}R{L|R}

model_code: “{Modelo}”

fit: “R”

side: “{L|R}”

✅ Checklist de exportación

Escala normalizada (montura X ≈ 0.145 m), Ctrl+A → All Transforms.

Pivotes:

Montura → puente (idealmente 0,0,0).

Patillas → centro del eje de su bisagra.

Nombres según convención Y–W.

Custom Properties añadidas (Y/W exactos y tolerancias).

Slots abiertos en materiales: usa nombres estables (MAT_*), evita dependencias de listas fijas.

En glTF: incluir custom properties activado.
