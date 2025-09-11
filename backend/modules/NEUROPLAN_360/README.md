# NEUROPLAN_360 (módulo)

## Endpoints (expuestos por el core)
- `POST /module/activate` — ejecuta el módulo.
  - `payload.action = start | step | generate` (opcional; si no, modo directo).
  - `module = "NEUROPLAN_360"`.

## Contrato
- Ver `contract.yaml`. El modo directo exige `usuario`, `neurodiversidades`, `formato`, `contexto`.

## Esquema de DB
- `NEUROPLAN_360.sessions` (wizard por pasos)
- `NEUROPLAN_360.plans` (planes generados)

## Notas
- Requiere Google JWT (core).
- Recomendado activar allowlist:
