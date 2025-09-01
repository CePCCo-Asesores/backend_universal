# Backend Universal — Activador Modular para Asistentes

Este repositorio contiene un backend literal, diseñado para Render. No requiere consola, instalación manual ni frameworks. Cada módulo se activa por contrato y ruta, sin improvisación.

## 📁 Estructura

- `index.php`: activador universal
- `routes.yaml`: tabla de rutas activables
- `composer.json`: activa Symfony YAML automáticamente
- `controllers/`: lógica modular por función
- `contracts/`: contratos por módulo
- `validators/`: validador literal de contratos
- `tests/`: auditoría de activación

## 🧩 Activación en Render

1. Sube este repositorio a GitHub
2. En Render, crea un nuevo Web Service conectado al repo
3. Usa `index.php` como punto de entrada
4. Render instalará automáticamente las dependencias
5. Cada módulo se activa por `POST` a su ruta declarada

## 🧪 Auditoría

Ejecuta `tests/contract_test.php` para validar que cada módulo cumple su contrato antes de activarse.

## 🧱 Principios

- Literalidad
- Modularidad
- Trazabilidad
- No almacenamiento
- Activación por contrato
