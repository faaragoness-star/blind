# P1: Pruebas y CI

Referencia: docs/Plano De Plugins — Propuesta V1 (basado En Capas 1–5 Y T).md

- [ ] Pruebas alineadas con verify.* del repo (sin ampliar alcance).
- [ ] Empaquetado ZIP por plugin según doc.

Plano de Plugins — Propuesta v2 (Enterprise)

Decisión tomada: mantener 6 plugins (4 core + 2 helpers) con overlay solo visual en el tema hijo. Arquitectura alineada con Capas 1–5 y T; slots abiertos; Zero‑mensajes; snapshot inmutable; firma Ed25519.

## 6) Telemetría y A/B - Eventos: ui.ready/select/autocorrect/add_to_cart/error/snapshot_ready. - Dimensiones: producto_id, pieza_id, material_id, modelo_id, color_id, textura_id, acabado_id, variante_ab, device, locale, texture_source, tint_applied, slot_name y, por control, control_type/control_value_id. - KPI: tiempo a config, % autocorrecciones, éxito add‑to‑cart, latencias validate/verify. - A/B: orden Color↔Textura, carrusel vs grid, “Acabado por lado” (parejas L/R).

## 7) Packaging & versionado - Zips: slug-vMAJOR.MINOR.PATCH.zip con README, CHANGELOG, MANIFEST, scripts/verify.*. - Compatibilidad: N / N‑1 para APIs y firma (sig.vN). - CDN: invalidación controlada tras publicar snapshot; cache por sku_hash+snapshot_id.
