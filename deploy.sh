#!/bin/bash

echo "🔧 Iniciando despliegue UNIVERSAL_BACKEND_V1..."

# Validar entorno
if [ -z "$JWT_SECRET" ]; then
  echo "❌ JWT_SECRET no definido. Aborta despliegue."
  exit 1
fi

if [ -z "$ENV" ]; then
  echo "⚠️ ENV no definido. Usando 'produccion' por defecto."
  export ENV=produccion
fi

# Crear logs si no existen
mkdir -p logs
touch logs/adia.log logs/fabricador.log logs/oauth.log

# Confirmar contratos
echo "📜 Contratos disponibles:"
ls contracts/*_V1.yaml

# Confirmar rutas activables
echo "📍 Rutas activables:"
grep -E '^[[:space:]]{2}/' routes.yaml | awk -F: '{print $1}' | sed 's/^[[:space:]]*//'

# Confirmar monitor
echo "📡 Monitor configurado:"
grep ruta monitor.yaml | awk '{print $2}'

echo "✅ Despliegue listo. Backend activable en entorno $ENV"
