# Plugin 6 — G3D Vendor Base Helper (vendor guard, instancia única, rutas CDN/local)

# 

# 1\) Identidad

# 

# Objetivo: proveer utilidades de base para el ecosistema (guardas, loaders y rutas) que garanticen una sola instancia del visor/Three y rutas de assets consistentes (CDN ↔ local), además de checks de entorno y preloads (Draco/Meshopt). No expone UI pública ni modifica el catálogo.

# 

# 2\) Visibilidad

# 

# Front (público): no visible (sin UI). Aporta estabilidad y rendimiento: una sola instancia de visor/loader, preloads, rutas correctas y fallbacks silenciosos.

# 

# Admin: mínimo. Página de configuración y diagnóstico (solo para roles altos).

# 

# 3\) Alcance y límites

# 

# ✅ Vendor guard: detecta y evita doble carga de librerías (Three, loaders, polyfills, visor).

# 

# ✅ Instancia única: expone un singleton para el visor/loader compartido por los plugins del front.

# 

# ✅ Rutas de assets: helpers para resolver URLs (CDN preferente, fallback local).

# 

# ✅ Preloads: Draco/Meshopt, workers y WASM listos antes del primer render.

# 

# ✅ Feature‑detection: checks de capacidades del navegador (WebGL2, WASM, prefers-reduced-motion).

# 

# ✅ CSP/SRI: soporta configurar integrity y orígenes permitidos.

# 

# ❌ No toca snapshots/Reglas/i18n.

# 

# ❌ No firma/verifica ni hace validaciones.

# 

# 4\) Admin — Estructura / Secciones

# 

# General

# 

# prefer\_cdn (on/off)

# 

# cdn\_base\_url, local\_base\_url

# 

# Versiones/URLs de Draco (draco\_decoder\_url) y Meshopt (meshopt\_wasm\_url)

# 

# three\_runtime\_url (si no se empaqueta con el tema)

# 

# Rendimiento

# 

# max\_concurrent\_downloads (p. ej., 4)

# 

# request\_timeout\_ms (p. ej., 15000)

# 

# preload\_on\_idle (on/off)

# 

# Seguridad

# 

# CSP: lista de orígenes permitidos (CDN)

# 

# SRI: hashes integrity (opcional)

# 

# Diagnóstico

# 

# Tabla de detetección de capacidades (WebGL/EXTs, WASM, memoria aprox.)

# 

# Botón “Probar preloads” y “Limpiar cachés”

# 

# Log de última inicialización (timestamps y resultados)

# 

# 5\) Modelo de datos (config)

# 

# {

# &nbsp; "schema\_version": "1.0.0",

# &nbsp; "prefer\_cdn": true,

# &nbsp; "cdn\_base\_url": "https://cdn.example.com/models/",

# &nbsp; "local\_base\_url": "/wp-content/uploads/models/",

# &nbsp; "three\_runtime\_url": "/assets/three.min.js",

# &nbsp; "draco\_decoder\_url": "https://cdn.example.com/vendors/draco/",

# &nbsp; "meshopt\_wasm\_url": "https://cdn.example.com/vendors/meshopt/meshopt\_decoder.wasm",

# &nbsp; "max\_concurrent\_downloads": 4,

# &nbsp; "request\_timeout\_ms": 15000,

# &nbsp; "preload\_on\_idle": true,

# &nbsp; "csp": {"script\_src": \["'self'","https://cdn.example.com"], "img\_src": \["\*"]},

# &nbsp; "sri": {"three\_runtime": "sha384-…", "draco": "sha384-…"}

# }

# 

# 6\) API (helpers del front)

# 

# VendorBase.ensureSingleton(key: string, factory: ()=>T): T  → devuelve siempre la misma instancia para key (ej.: viewer, loader).

# 

# VendorBase.resolveUrl(path: string): string → resuelve CDN ↔ local según config y disponibilidad.

# 

# VendorBase.preloadDecoders(): Promise<void> → precarga Draco/Meshopt (WASM/workers) antes del primer render.

# 

# VendorBase.detect(): Capabilities → devuelve capacidades y hints ({ webgl2: true, wasm: true, reducedMotion: false, … }).

# 

# VendorBase.vendorGuard(): void → avisa (solo consola/log) si detecta doble carga o conflictos de versiones.

# 

# Eventos emitidos: vendor.ready, vendor.preloaded, vendor.warning (para observabilidad ligera).

# 

# 7\) RBAC

# 

# Admin: edita configuración (rutas, límites, CSP/SRI).

# 

# Operador: ejecuta diagnóstico, limpia cachés.

# 

# Lectura: sin acceso al panel.

# 

# 8\) Flujos críticos

# 

# Init (en DOMContentLoaded o cuando el Wizard lo pida): vendorGuard() → ensureSingleton('loader') → preloadDecoders().

# 

# Resolución de rutas: resolveUrl() ante cada asset; si CDN falla → fallback local.

# 

# Instancia única de visor: ensureSingleton('viewer', factory) desde Wizard; si ya existe, reutiliza.

# 

# 9\) Errores \& Zero‑mensajes

# 

# No muestra errores al usuario final. Registra en logs/consola y expone eventos vendor.warning.

# 

# Códigos de diagnóstico (internos): E\_VENDOR\_DUPLICATE, E\_DECODER\_PRELOAD\_FAIL, E\_URL\_RESOLVE\_FAIL.

# 

# 10\) Rendimiento

# 

# Objetivo: primer frame < 200 ms (apoya con preloads y singleton).

# 

# Preload on idle: usa requestIdleCallback/scheduler.postTask cuando haya.

# 

# Limita concurrencia de descargas; respeta Connection del navegador.

# 

# 11\) Observabilidad

# 

# Pequeño beacon opcional con request\_id (cuando lo provee Wizard/Validate).

# 

# KPIs: tiempo a preloads, tasa de fallback CDN→local, incidencias de vendor.warning.

# 

# 12\) Seguridad

# 

# CSP y SRI configurables.

# 

# Evita eval/inline si no hay hashes.

# 

# No expone secretos; solo rutas públicas.

# 

# 13\) Versionado \& Packaging

# 

# g3d-vendor-base-helper-vX.Y.Z.zip con README/CHANGELOG/MANIFEST.

# 

# SemVer para la config; compatibilidad N/N‑1 con Wizard.

# 

# 14\) Backups \& DR

# 

# Export/Import de config (JSON). Backup diario junto con opciones del sitio. RPO ≤ 24 h, RTO ≤ 4 h.

# 

# 15\) QA \& Checklists

# 

# Antes de activar: probar preloadDecoders, validar CSP/SRI, verificar singleton en páginas con varios módulos.

# 

# En producción: monitorear fallbacks CDN→local; revisar tamaño/latencia de decoders; comprobar que no hay doble instancia del visor.

# 

# Con esto, el Vendor Base Helper establece una base común para que Wizard y el resto de plugins funcionen con una sola instancia, rutas de assets uniformes y preparación de decoders sin fricciones ni mensajes al usuario.

# 



