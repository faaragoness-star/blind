Capa 3 — Validación, Firma, Caducidad y Errores (ACTUALIZADA — Slots abiertos) — v2 (URLs)

Objetivo

Validar cualquier estado contra un snapshot publicado e inmutable.

Emitir un SKU reproducible (hash determinista) y firma con caducidad.

Definir APIs, códigos de error y severidades para logs/Admin, manteniendo Zero-Mensajes en el front.

Conceptos base

Snapshot publicado como fuente de verdad.

Estado de usuario = { piezas: \[…], morphs: {…} }.

SKU canónico = serialización pieza→mat→modelo→col→tex→fin.

Slots abiertos: el GLB puede traer cualquier slot embebido con cualquier nombre. Los slots son identificadores técnicos del GLB; su uso depende del mapeo editorial definido en Admin (controles por slot).

Zero-Mensajes: inconsistencias se corrigen en silencio.

API 2.1 POST /validate-sign

Request: schema\_version, snapshot\_id, producto\_id, state, locale, flags opcionales.

Validaciones: snapshot existe, IDs válidos, matrices, encaje, safety, color HEX, precio/stock coherentes, controles por slot aplicados según Admin.

• Selecciones por slot coherentes con slots\_detectados del modelo/pieza; si no, E\_INVALID\_ID/E\_RULE\_VIOLATION.

Response ok:true: sku\_hash, sku\_signature, expires\_at, snapshot\_id, summary, price, stock, photo\_url, request\_id.

Response ok:false: code estándar + reason\_key + detail + request\_id.

Front: autocorrige y reintenta 1 vez; si persiste, reabre configurador ajustado.

2.2 POST /verify

Request: sku\_hash, sku\_signature, snapshot\_id.

Response: ok:true o error (E\_SIGN\_EXPIRED, E\_SIGN\_INVALID, etc.).

Front: reconstruye estado desde snapshot vigente si falla.

Nota de URL/Path: el servidor IGNORA alias/segmentos de path (p. ej., /gafas/@alias/…); valida exclusivamente sku\_hash, sku\_signature y snapshot\_id.

SKU, firma y caducidad

sku\_hash: SHA-256 de JSON canónico ordenado; null omitido; arrays orden editorial.

Morphs: por defecto no en SKU; opcional incluir con rounding y orden.

sku\_signature: cubre sku\_hash + snapshot\_id + expiración + locale + ab\_variant.

Caducidad: 30 días (configurable).

Algoritmo: Ed25519; claves en bóveda.

Precio/Stock opcionales; cambios posteriores generan errores en /verify.

Snapshot imagen opcional, TTL 90 días.

Zero-Mensajes — front

computeAllowed = matrices + encaje + safety + textura rules + controles por slot.

Autoselección: último válido → default → primer válido.

Add to cart: /validate-sign, autocorrección y retry. Si falla, reabrir configurador ajustado.

Assets: mantener último frame si falla; no mostrar opciones imposibles.

Códigos de error estándar

E\_INVALID\_ID, E\_SCHEMA\_VERSION, E\_RULE\_VIOLATION, E\_TEXTURE\_DEFINES\_COLOR, E\_ENCAJE\_FAILED, E\_MORPH\_RANGE, E\_SAFETY\_LIMIT, E\_ASSET\_MISSING, E\_SIGN\_INVALID, E\_SIGN\_EXPIRED, E\_SIGN\_SNAPSHOT\_MISMATCH, E\_PRICE\_OUT\_OF\_DATE, E\_STOCK\_UNAVAILABLE, E\_LINK\_EXPIRED, E\_TIMEOUT, E\_RATE\_LIMIT, E\_UNSUPPORTED\_FEATURE, E\_INTERNAL.

reason\_key en snake\_case para analítica.

Caso textura define color = siempre E\_TEXTURE\_DEFINES\_COLOR (info).

Seguridad y operación

TLS obligatorio, CORS lista blanca, rate limiting, idempotency\_key.

Auditoría: quién/cuándo/qué, snapshot\_id, resultado, latencias.

Privacidad: sin PII en payloads. El alias de usuario puede aparecer en el path (estético) pero no forma parte de payloads ni afecta validación.

Resiliencia: fallback de assets.

Gestión de claves: bóveda, rotación programada, acceso mínimo.

Logging: request\_id incluido.

Compatibilidad y versionado

schema\_version SemVer en payloads.

Firma con prefijo sig.vN y convivencia N/N-1.

Snapshot drift: si snapshot viejo, /verify puede fallar → front rehace estado.

Checklists

Back/Admin: snapshot único, firma activa, caducidad 30 días, auditoría, rate-limit, staging probado.

Front: computeAllowed completo, autocorrección, /validate-sign con retry, Zero-mensajes, último frame estable.

Checkout: siempre /verify, reconstrucción silenciosa.

Ejemplos

Ciclo típico: config → validate-sign → ok:true → carrito → verify → ok:true.

Payload add-to-cart incluye sku\_hash, sku\_signature, qty, summary, photo\_url.

Notas finales

snapshot\_id estándar y externo.

Morphs fuera por defecto, opcional incluir.

Slots abiertos y mapeados editorialmente; el front solo refleja el comportamiento definido por Admin.

Caso lente: textura fija no tintable salvo override.

Error mapping unificado con reason\_key estable.

