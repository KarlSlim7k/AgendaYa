<?php
/**
 * Script de mantenimiento para limpiar cache en hosting compartido (Neubox/cPanel).
 *
 * USO:
 *   1. Subir este archivo a la carpeta public/ del proyecto.
 *   2. Acceder desde el navegador con la clave secreta:
 *      https://tudominio.com/maintenance.php?key=TU_CLAVE_SECRETA
 *   3. Ejecutar la accion deseada.
 *   4. ELIMINAR este archivo despues de usarlo por seguridad.
 *
 * ACCIONES DISPONIBLES:
 *   ?key=TU_CLAVE&action=clear-all          -> Limpia todo (config, routes, views, cache general)
 *   ?key=TU_CLAVE&action=clear-config       -> Limpia solo config cache
 *   ?key=TU_CLAVE&action=clear-routes       -> Limpia solo routes cache
 *   ?key=TU_CLAVE&action=clear-views        -> Limpia solo views cache
 *   ?key=TU_CLAVE&action=clear-cache        -> Limpia solo application cache
 *   ?key=TU_CLAVE&action=optimize           -> Optimiza (cachea config + routes + views)
 *   ?key=TU_CLAVE&action=optimize-clear     -> Limpia todas las optimizaciones
 *   ?key=TU_CLAVE&action=info               -> Muestra informacion del entorno
 *
 * IMPORTANTE:
 *   - Cambia CLAVE_SECRETA_AQUI por una contraseña fuerte antes de subir.
 *   - ELIMINA este archivo despues de usarlo.
 */

// ============================================
// CONFIGURA TU CLAVE SECRETA AQUI
// ============================================
define('SECRET_KEY', 'delgado123');

// Verificar clave
$key = $_GET['key'] ?? '';
if ($key !== SECRET_KEY) {
    http_response_code(403);
    die('<h1>403 - Acceso denegado</h1><p>Proporciona una clave valida: ?key=TU_CLAVE</p>');
}

// ============================================
// AUTO-DETECTAR RUTA DE LARAVEL
// ============================================
// En Neubox/cPanel la estructura puede variar:
// - public_html/ = public/ de Laravel
// - Los archivos de Laravel pueden estar en el directorio padre o en otra ubicacion

function findLaravelBase($currentDir) {
    // Rutas directas a probar (en orden de probabilidad para Neubox)
    $possiblePaths = [
        '/home/agendaya/agendaya_app',          // Ruta absoluta directa
        dirname($currentDir) . '/agendaya_app', // /home/agendaya/agendaya_app
        $currentDir . '/../agendaya_app',       // relative
    ];

    foreach ($possiblePaths as $path) {
        // Verificar sin realpath primero
        if (file_exists($path . '/artisan') || file_exists($path . '/bootstrap/app.php')) {
            return rtrim($path, '/');
        }
        // Intentar con realpath
        $real = @realpath($path);
        if ($real && (file_exists($real . '/artisan') || file_exists($real . '/bootstrap/app.php'))) {
            return $real;
        }
    }

    // Busqueda recursiva: subir hasta 5 niveles y buscar artisan
    $path = $currentDir;
    for ($i = 0; $i < 5; $i++) {
        $parent = dirname($path);
        if ($parent === $path) break;
        
        if (file_exists($parent . '/artisan')) {
            return $parent;
        }
        
        // Buscar en hermanos del padre
        if (is_dir($parent)) {
            $items = @scandir($parent);
            if ($items) {
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') continue;
                    $sibling = $parent . '/' . $item;
                    if (is_dir($sibling) && file_exists($sibling . '/artisan')) {
                        return $sibling;
                    }
                }
            }
        }
        $path = $parent;
    }

    return null;
}

$laravelBase = findLaravelBase(__DIR__);

if (!$laravelBase) {
    // Mostrar diagnostico detallado
    echo '<h1>Error: No se encontro la instalacion de Laravel</h1>';
    echo '<p>Rutas probadas:</p><ul>';
    
    $testPaths = [
        '/home/agendaya/agendaya_app',
        dirname(__DIR__) . '/agendaya_app',
        __DIR__ . '/../agendaya_app',
    ];
    
    foreach ($testPaths as $tp) {
        $exists = file_exists($tp . '/artisan') ? '✅ artisan existe' : '❌ no artisan';
        $bootstrapExists = file_exists($tp . '/bootstrap/app.php') ? '✅ bootstrap existe' : '❌ no bootstrap';
        $isDir = is_dir($tp) ? '📁 es directorio' : '❌ no es directorio';
        echo "<li><code>$tp</code> — $isDir — $exists — $bootstrapExists</li>";
    }
    
    echo '</ul>';
    echo '<p>Directorio actual: <code>' . __DIR__ . '</code></p>';
    echo '<p>Directorio padre: <code>' . dirname(__DIR__) . '</code></p>';
    die();
}

$bootstrap = $laravelBase . '/bootstrap/app.php';
$artisan = $laravelBase . '/artisan';

// Accion
$action = $_GET['action'] ?? 'info';

// Funcion helper para ejecutar comandos de Artisan
function runArtisan($command, $description, $laravelBase) {
    $output = [];
    $return = 0;
    
    // Intentar via Artisan CLI
    $artisan = $laravelBase . '/artisan';
    if (file_exists($artisan)) {
        exec("php $artisan $command 2>&1", $output, $return);
    }
    
    // Si falla, intentar manualmente borrando archivos de cache
    if ($return !== 0) {
        $output = manualCacheClear($command, $output, $laravelBase);
        $return = 0;
    }
    
    return [
        'command' => $command,
        'description' => $description,
        'success' => $return === 0,
        'output' => implode("\n", $output),
    ];
}

// Limpieza manual de archivos de cache (fallback)
function manualCacheClear($command, $output, $laravelBase) {
    $cachePath = $laravelBase . '/bootstrap/cache';
    
    if (!is_dir($cachePath)) {
        $output[] = "Directorio de cache no encontrado: $cachePath";
        return $output;
    }
    
    $files = [
        'config:clear' => ['config.php'],
        'route:clear' => ['routes-v7.php', 'routes.php'],
        'view:clear' => [],
        'cache:clear' => [],
        'optimize:clear' => ['config.php', 'routes-v7.php', 'routes.php', 'packages.php', 'services.php'],
    ];
    
    $filesToClear = $files[$command] ?? [];
    
    foreach ($filesToClear as $file) {
        $filePath = $cachePath . '/' . $file;
        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                $output[] = "Eliminado: $file";
            } else {
                $output[] = "Error al eliminar: $file (verifica permisos)";
            }
        } else {
            $output[] = "No existe: $file (ya estaba limpio)";
        }
    }
    
    // Para view:clear, limpiar storage/framework/views
    if ($command === 'view:clear' || $command === 'optimize:clear') {
        $viewsPath = $laravelBase . '/storage/framework/views';
        if (is_dir($viewsPath)) {
            $count = 0;
            foreach (glob($viewsPath . '/*') as $viewFile) {
                if (is_file($viewFile) && unlink($viewFile)) {
                    $count++;
                }
            }
            $output[] = "Vistas compilidas eliminadas: $count archivos";
        }
    }
    
    // Para cache:clear, limpiar storage/framework/cache/data
    if ($command === 'cache:clear' || $command === 'optimize:clear') {
        $cacheDataPath = $laravelBase . '/storage/framework/cache/data';
        if (is_dir($cacheDataPath)) {
            $count = 0;
            foreach (glob($cacheDataPath . '/*') as $cacheFile) {
                if (is_file($cacheFile) && unlink($cacheFile)) {
                    $count++;
                }
            }
            $output[] = "Archivos de cache eliminados: $count archivos";
        }
    }
    
    return $output;
}

// Procesar accion
$results = [];

switch ($action) {
    case 'clear-config':
        $results[] = runArtisan('config:clear', 'Limpiar cache de configuracion', $laravelBase);
        break;
        
    case 'clear-routes':
        $results[] = runArtisan('route:clear', 'Limpiar cache de rutas', $laravelBase);
        break;
        
    case 'clear-views':
        $results[] = runArtisan('view:clear', 'Limpiar cache de vistas', $laravelBase);
        break;
        
    case 'clear-cache':
        $results[] = runArtisan('cache:clear', 'Limpiar cache de aplicacion', $laravelBase);
        break;
        
    case 'clear-all':
    case 'optimize-clear':
        $results[] = runArtisan('optimize:clear', 'Limpiar todas las optimizaciones', $laravelBase);
        break;
        
    case 'optimize':
        $results[] = runArtisan('config:cache', 'Cachear configuracion', $laravelBase);
        $results[] = runArtisan('route:cache', 'Cachear rutas', $laravelBase);
        $results[] = runArtisan('view:cache', 'Cachear vistas', $laravelBase);
        break;
        
    case 'info':
    default:
        $results = ['info' => true];
        break;
}

// ============================================
// HTML DE RESPUESTA
// ============================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - AgendaYa</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #e2e8f0; padding: 2rem; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { font-size: 1.5rem; margin-bottom: 0.5rem; color: #38bdf8; }
        .subtitle { color: #94a3b8; margin-bottom: 2rem; font-size: 0.875rem; }
        .warning { background: #fef3c7; color: #92400e; padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem; font-size: 0.875rem; }
        .warning strong { color: #78350f; }
        .actions { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem; margin-bottom: 2rem; }
        .action-btn { display: block; padding: 0.75rem 1rem; background: #1e293b; border: 1px solid #334155; border-radius: 0.5rem; color: #e2e8f0; text-decoration: none; font-size: 0.875rem; transition: all 0.2s; }
        .action-btn:hover { background: #334155; border-color: #38bdf8; }
        .action-btn.danger { border-color: #ef4444; color: #fca5a5; }
        .action-btn.danger:hover { background: #7f1d1d; }
        .action-btn.success { border-color: #22c55e; color: #86efac; }
        .action-btn.success:hover { background: #14532d; }
        .result { background: #1e293b; border: 1px solid #334155; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem; }
        .result h3 { font-size: 0.875rem; color: #38bdf8; margin-bottom: 0.5rem; }
        .result pre { background: #0f172a; padding: 0.75rem; border-radius: 0.375rem; font-size: 0.75rem; overflow-x: auto; white-space: pre-wrap; color: #94a3b8; }
        .result.success { border-color: #22c55e; }
        .result.error { border-color: #ef4444; }
        .info-grid { display: grid; gap: 0.5rem; }
        .info-item { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #1e293b; }
        .info-item span:first-child { color: #94a3b8; }
        .info-item span:last-child { color: #e2e8f0; font-family: monospace; }
        .back-link { display: inline-block; margin-top: 1rem; color: #38bdf8; text-decoration: none; font-size: 0.875rem; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Maintenance - AgendaYa</h1>
        <p class="subtitle">Herramienta de mantenimiento para hosting compartido</p>

        <div class="warning">
            <strong>⚠️ IMPORTANTE:</strong> Elimina este archivo despues de usarlo. No lo dejes en produccion.
        </div>

        <?php if ($action === 'info'): ?>
            <div class="info-grid">
                <div class="info-item"><span>PHP Version</span><span><?= PHP_VERSION ?></span></div>
                <div class="info-item"><span>APP_ENV</span><span><?= $_ENV['APP_ENV'] ?? 'no definido' ?></span></div>
                <div class="info-item"><span>APP_URL</span><span><?= $_ENV['APP_URL'] ?? 'no definido' ?></span></div>
                <div class="info-item"><span>APP_DEBUG</span><span><?= $_ENV['APP_DEBUG'] ?? 'no definido' ?></span></div>
                <div class="info-item"><span>Laravel Base</span><span><?= $laravelBase ?></span></div>
                <div class="info-item"><span>Bootstrap</span><span><?= file_exists($bootstrap) ? '✅ Existe' : '❌ No existe' ?></span></div>
                <div class="info-item"><span>Artisan</span><span><?= file_exists($artisan) ? '✅ Existe' : '❌ No existe' ?></span></div>
                <div class="info-item"><span>Cache Writable</span><span><?= is_writable($laravelBase . '/bootstrap/cache') ? '✅ Si' : '❌ No' ?></span></div>
                <div class="info-item"><span>Storage Writable</span><span><?= is_writable($laravelBase . '/storage') ? '✅ Si' : '❌ No' ?></span></div>
            </div>

            <div class="actions" style="margin-top: 2rem;">
                <a href="?key=<?= SECRET_KEY ?>&action=clear-all" class="action-btn success">🧹 Limpiar todo</a>
                <a href="?key=<?= SECRET_KEY ?>&action=clear-config" class="action-btn">Config Cache</a>
                <a href="?key=<?= SECRET_KEY ?>&action=clear-routes" class="action-btn">Routes Cache</a>
                <a href="?key=<?= SECRET_KEY ?>&action=clear-views" class="action-btn">Views Cache</a>
                <a href="?key=<?= SECRET_KEY ?>&action=clear-cache" class="action-btn">App Cache</a>
                <a href="?key=<?= SECRET_KEY ?>&action=optimize" class="action-btn success">⚡ Optimizar</a>
                <a href="?key=<?= SECRET_KEY ?>&action=optimize-clear" class="action-btn danger">🗑️ Des-optimizar</a>
            </div>

        <?php else: ?>
            <?php foreach ($results as $result): ?>
                <div class="result <?= $result['success'] ? 'success' : 'error' ?>">
                    <h3><?= $result['description'] ?> - <?= $result['success'] ? '✅ Exitoso' : '❌ Error' ?></h3>
                    <pre><?= htmlspecialchars($result['output'] ?: 'Sin salida') ?></pre>
                </div>
            <?php endforeach; ?>

            <a href="?key=<?= SECRET_KEY ?>&action=info" class="back-link">&larr; Volver al panel</a>
        <?php endif; ?>
    </div>
</body>
</html>
