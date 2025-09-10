<?php
require_once 'ContractValidator.php';

$validador = new ContractValidator();

$tests = [
    'ADIA_V1' => ['contexto' => 'soporte emocional'],
    'FABRICADOR_V1' => ['blueprint' => 'viajes.yaml'],
    'FABRICADOR_V1_INVALIDO' => ['licencia' => 'GPL']
];

foreach ($tests as $modulo => $input) {
    echo "🔍 Test: $modulo\n";
    $resultado = $validador->validar(str_replace('_INVALIDO', '', $modulo), $input);
    echo json_encode($resultado, JSON_PRETTY_PRINT) . "\n\n";
}
