# blind (monorepo)

Proyecto enterprise (nivel Nike/Amazon) para un configurador 3D hiperpersonalizable.
- Arquitectura en 6 plugins (P1..P5 + helper)
- Slots abiertos, snapshot inmutable, SKU determinista, firma Ed25519
- CI con verify.* y empaquetado ZIP por plugin

## Estructura


plugins/ # 6 plugins (esqueletos)
docs/ # capas, decisiones, guardarraíles
assets/ # modelos GLB de prueba (no sensibles)
scripts/ # verify.* (repo, plugins, assets)
.github/ # workflows CI


## Cómo contribuir
Lee `CONTRIBUTING.md` y respeta ramas protegidas: todo via PR con checks verdes.
