<?php
if (!isset($_GET['key']) || $_GET['key'] !== 'agendaya2026') {
    http_response_code(404);
    die('Not found');
}

$hash = password_hash('password123', PASSWORD_BCRYPT, ['cost' => 12]);
$valid = password_verify('password123', $hash);

echo "<p>Hash: <code>" . htmlspecialchars($hash) . "</code></p>";
echo "<p>Longitud: " . strlen($hash) . " caracteres</p>";
echo "<p>Verificación: " . ($valid ? '✓ Correcta' : '✗ Falló') . "</p>";
echo "<p><strong>Copia el hash de arriba y ejecuta en phpMyAdmin:</strong></p>";
echo "<pre>UPDATE users SET password = '" . htmlspecialchars($hash) . "' WHERE id = 8;</pre>";
echo "<p style='color:red'><strong>BORRA ESTE ARCHIVO después de usarlo.</strong></p>";
