# Guardarraíles para Codex
- Cambios SOLO en ramas (nunca en `main`).
- Codex no puede borrar ni renombrar archivos existentes sin aprobación explícita.
- Paths permitidos: docs/, assets/, scripts/, .github/workflows/, plugins/*/README.md
- Antes de modificar código de un plugin, abrir PR con descripción, impacto y checklist DoD.
- CI obligatorio: `ci-verify` (+ `ci-lint-php` si aplica) verde.
- Rollback documentado en el PR.
