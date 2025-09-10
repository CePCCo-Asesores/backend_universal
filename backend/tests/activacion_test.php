<?php
function post(string $url, array $data): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($data)
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $resp];
}

// ADIA
list($code1, $resp1) = post('http://localhost/adia_generate.php', ['contexto' => 'soporte emocional']);
echo "ADIA: $code1 $resp1\n";
if ($code1 < 200 || $code1 >= 300) exit(1);

// FABRICADOR
list($code2, $resp2) = post('http://localhost/fabricador_generate.php', ['blueprint' => 'fabricacion.yaml']);
echo "FABRICADOR: $code2 $resp2\n";
if ($code2 < 200 || $code2 >= 300) exit(1);
