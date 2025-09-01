<?php
function registrarModulo($nombre, $ruta, $controller, $action) {
    $archivo = __DIR__ . '/routes.yaml';
    $lineas = file($archivo);
    $nuevo = "$ruta:\n  controller: $controller\n  action: $action\n";

    // Verificar si ya existe
    foreach ($lineas as $linea) {
        if (strpos($linea, $ruta . ':') !== false) {
            echo "❌ Ruta ya registrada: $ruta\n";
            return;
        }
    }

    // Agregar al final
    file_put_contents($archivo, "\n" . $nuevo, FILE_APPEND);
    echo "✅ Módulo registrado: $nombre → $ruta\n";
}

// Ejemplo de uso
registrarModulo('EMOCIONAL_V1', '/emocional_generate.php', 'AgentController', 'activarEmocional');
