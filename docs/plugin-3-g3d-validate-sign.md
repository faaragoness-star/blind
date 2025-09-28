# Plugin 3 — G3D Validate \& Sign (SKU determinista, firma Ed25519 y verificación)

# 1\) Identidad

# &nbsp;   • Objetivo: validar cualquier estado de usuario contra un snapshot publicado (inmutable), generar un SKU determinista (sku\_hash) y devolver una firma con caducidad. Proveer endpoint de verificación en checkout.

# 2\) Visibilidad

# &nbsp;   • Front (público): sin UI propia. Aporta resultado directo al flujo: { sku\_hash, sku\_signature, expires\_at, snapshot\_id, summary, price?, stock?, photo\_url?, request\_id }.

# &nbsp;   • Admin: sí (operación). Consolas para claves, políticas, auditoría y salud.

# 3\) Alcance y límites

# &nbsp;   • ✅ /validate-sign: valida input + serializa estado → sku\_hash (SHA‑256 JSON canónico) → firma (sig.vN Ed25519) con expiración (30 días por defecto).

# &nbsp;   • ✅ /verify: comprueba firma/expiración/snapshot.

# &nbsp;   • ✅ Zero‑mensajes de cara al front: autocorrecciones silenciosas y retry recomendado 1 vez desde el cliente.

# &nbsp;   • ❌ No edita catálogo/snapshots (solo lectura).

# &nbsp;   • ❌ No renderiza UI.

# 4\) Admin — Estructura / Secciones

# &nbsp;   • 4.1 Claves y firma

# &nbsp;       ◦ Algoritmo Ed25519. Gestión en bóveda. Rotación N/N+1 (convivencia sig.vN y sig.vN‑1).

# &nbsp;       ◦ Parámetros: caducidad por defecto (30 días), prefijo sig.vN.

# &nbsp;   • 4.2 Políticas

# &nbsp;       ◦ CORS (lista blanca), rate‑limit, tamaño máximo de payload, idempotency\_key.

# &nbsp;   • 4.3 Auditoría \& logs

# &nbsp;       ◦ Registro: quién/cuándo/qué (request\_id, snapshot\_id, resultado, latencias).

# &nbsp;       ◦ Panel de errores por code y reason\_key.

# &nbsp;   • 4.4 Salud \& entornos

# &nbsp;       ◦ Health checks, staging/producción, test de snapshot drift.

# 5\) Modelo de datos (operacional)

# &nbsp;   • Config: sign\_version, default\_ttl\_days, allowed\_origins\[], rate\_limits, keys{active, next}.

# &nbsp;   • AuditLog: timestamp, request\_id, snapshot\_id, ok, code, reason\_key, latency\_ms.

# 6\) APIs / Contratos

# 6.1 POST /validate-sign

# &nbsp;   • Request: schema\_version, snapshot\_id, producto\_id, state, locale, flags?.

# &nbsp;       ◦ state serializa pieza→mat→modelo→col→tex→fin; si hay controles por slot marcados affects\_sku:true, se incluyen por pieza/slot.

# &nbsp;   • Validaciones (extracto): snapshot existe; IDs válidos; matrices/encaje/safety; color HEX; controles por slot coherentes con slots\_detectados; coherencia precio/stock si vienen.

# &nbsp;   • Response ok:true: sku\_hash, sku\_signature, expires\_at, snapshot\_id, summary, price?, stock?, photo\_url?, request\_id.

# &nbsp;   • Response ok:false: code estándar + reason\_key + detail + request\_id.

# 6.2 POST /verify

# &nbsp;   • Request: sku\_hash, sku\_signature, snapshot\_id.

# &nbsp;   • Response: ok:true o error (E\_SIGN\_EXPIRED, E\_SIGN\_INVALID, E\_SIGN\_SNAPSHOT\_MISMATCH, etc.).

# Nota: El servidor ignora alias/segmentos de path (p. ej. /gafas/@alias/…); valida exclusivamente sku\_hash, sku\_signature y snapshot\_id.

# 7\) RBAC

# &nbsp;   • Operador: ver auditoría, ajustar políticas (rate‑limit/CORS/TTL).

# &nbsp;   • Admin: rotar claves, activar sig.vN+1, configurar bóveda.

# &nbsp;   • Solo lectura: consultar métricas/logs.

# 8\) Flujos críticos (front)

# &nbsp;   1) Add to cart → cliente llama /validate-sign → si ok:false con error recuperable, autocorrección y retry (1); si persiste, reabrir configurador ajustado.

# &nbsp;   2) Checkout → /verify → si falla (caducidad, drift), reconstruir estado desde snapshot vigente en silencio.

# &nbsp;   3) Screenshot: URL opcional (photo\_url) con TTL 90 días.

# 9\) Errores \& Zero‑mensajes

# &nbsp;   • Códigos estándar: E\_INVALID\_ID, E\_SCHEMA\_VERSION, E\_RULE\_VIOLATION, E\_TEXTURE\_DEFINES\_COLOR, E\_ENCAJE\_FAILED, E\_MORPH\_RANGE, E\_SAFETY\_LIMIT, E\_ASSET\_MISSING, E\_SIGN\_INVALID, E\_SIGN\_EXPIRED, E\_SIGN\_SNAPSHOT\_MISMATCH, E\_PRICE\_OUT\_OF\_DATE, E\_STOCK\_UNAVAILABLE, E\_LINK\_EXPIRED, E\_TIMEOUT, E\_RATE\_LIMIT, E\_UNSUPPORTED\_FEATURE, E\_INTERNAL.

# &nbsp;   • reason\_key en snake\_case para analítica. Caso Textura define color siempre devuelve E\_TEXTURE\_DEFINES\_COLOR (informativo).

# 10\) Rendimiento \& SLO

# &nbsp;   • Objetivo: p95 /validate-sign ≤ 150 ms en snapshot caliente.

# &nbsp;   • Firmas y hashes en memoria + caché por snapshot\_id.

# 11\) Observabilidad

# &nbsp;   • Incluir request\_id en todos los responses.

# &nbsp;   • Métricas: tasa de ok, distribución de code/reason\_key, latencia por endpoint, ratio de autocorrección.

# 12\) Seguridad \& Operación

# &nbsp;   • TLS obligatorio; CORS de lista blanca; rate limiting; idempotency\_key.

# &nbsp;   • Gestión de claves en bóveda, rotación programada, acceso mínimo.

# &nbsp;   • Privacidad: sin PII en payloads; el alias de usuario puede aparecer en path (estético) pero no forma parte de payloads ni afecta validación.

# 13\) Versionado \& Convivencia

# &nbsp;   • schema\_version SemVer en payloads.

# &nbsp;   • Firma con prefijo sig.vN, convivencia N/N‑1 durante rotación.

# &nbsp;   • Snapshot drift: si snapshot viejo, /verify puede fallar; el front rehace estado en silencio.

# 14\) Backups \& DR

# &nbsp;   • Auditoría/logs con retención 90 días. Backups diarios de configuración/clave activa (sin exponer privadas fuera de bóveda). Pruebas de restauración.

# 15\) QA \& Checklists

# &nbsp;   • Back/Admin: snapshot único accesible; firma activa; caducidad 30 días; auditoría; rate‑limit; staging probado.

# &nbsp;   • Front: computeAllowed completo; autocorrección; /validate-sign con retry; Zero‑mensajes; último frame estable.

# &nbsp;   • Checkout: siempre /verify; reconstrucción silenciosa en caso de expiración.

# 

# Este informe está alineado con slots abiertos, Snapshot inmutable, Zero‑mensajes, firma Ed25519 y URLs donde el alias es solo estético.

