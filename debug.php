<?php
/**
 * Debug Script for Error 500
 * Upload this as debug.php and visit: https://adintel.redbananas.com/debug.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Adintel Debug - Error 500 Diagnosis</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 1000px; margin: 0 auto; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .section { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        pre { background: #e9ecef; padding: 10px; border-radius: 4px; overflow-x: auto; }
        h2 { color: #495057; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Adintel Error 500 Debug</h1>
        
        <div class="section">
            <h2>📋 System Information</h2>
            <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
            <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
            <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></p>
            <p><strong>Current Directory:</strong> <?php echo __DIR__; ?></p>
            <p><strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?></p>
            <p><strong>Max Execution Time:</strong> <?php echo ini_get('max_execution_time'); ?>s</p>
        </div>

        <div class="section">
            <h2>📁 Critical Files Check</h2>
            <?php
            $criticalFiles = [
                'vendor/autoload.php' => 'Composer autoloader',
                'bootstrap/app.php' => 'Laravel bootstrap',
                '.env' => 'Environment configuration',
                'storage/' => 'Storage directory',
                'storage/logs/' => 'Log directory',
                'storage/framework/' => 'Framework cache directory',
                'storage/framework/cache/' => 'Cache directory',
                'storage/framework/sessions/' => 'Sessions directory',
                'storage/framework/views/' => 'Views cache directory',
                'bootstrap/cache/' => 'Bootstrap cache directory',
                'config/app.php' => 'App configuration',
                'config/database.php' => 'Database configuration'
            ];

            foreach ($criticalFiles as $file => $description) {
                $path = __DIR__ . '/' . $file;
                $exists = file_exists($path);
                $readable = $exists && is_readable($path);
                $writable = $exists && is_writable($path);
                
                echo '<p>';
                echo $exists ? '<span class="success">✅</span>' : '<span class="error">❌</span>';
                echo " <strong>$file:</strong> $description ";
                
                if ($exists) {
                    if (is_dir($path)) {
                        $perms = substr(sprintf('%o', fileperms($path)), -4);
                        echo " (Dir, $perms)";
                        if (!$writable) echo ' <span class="error">[NOT WRITABLE]</span>';
                    } else {
                        $size = filesize($path);
                        $perms = substr(sprintf('%o', fileperms($path)), -4);
                        echo " ($size bytes, $perms)";
                        if (!$readable) echo ' <span class="error">[NOT READABLE]</span>';
                    }
                } else {
                    echo ' <span class="error">[MISSING]</span>';
                }
                echo '</p>';
            }
            ?>
        </div>

        <div class="section">
            <h2>🔧 Directory Permissions</h2>
            <?php
            $directories = ['storage', 'storage/logs', 'storage/framework', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'bootstrap/cache'];
            
            foreach ($directories as $dir) {
                $path = __DIR__ . '/' . $dir;
                if (is_dir($path)) {
                    $perms = substr(sprintf('%o', fileperms($path)), -4);
                    $writable = is_writable($path);
                    $color = $writable ? 'success' : 'error';
                    $status = $writable ? 'WRITABLE' : 'NOT WRITABLE';
                    echo "<p><span class='$color'>$perms</span> <strong>$dir/</strong> [$status]</p>";
                } else {
                    echo "<p><span class='error'>MISSING</span> <strong>$dir/</strong></p>";
                }
            }
            ?>
        </div>

        <div class="section">
            <h2>🔍 Environment Check</h2>
            <?php
            if (file_exists(__DIR__ . '/.env')) {
                echo '<p class="success">✅ .env file exists</p>';
                
                $envContent = file_get_contents(__DIR__ . '/.env');
                $hasAppKey = strpos($envContent, 'APP_KEY=base64:') !== false && strpos($envContent, 'YOUR_GENERATED_KEY_HERE') === false;
                $hasDbConfig = strpos($envContent, 'DB_DATABASE=') !== false;
                $appDebug = strpos($envContent, 'APP_DEBUG=true') !== false;
                
                echo '<p>' . ($hasAppKey ? '<span class="success">✅</span>' : '<span class="error">❌</span>') . ' APP_KEY configured</p>';
                echo '<p>' . ($hasDbConfig ? '<span class="success">✅</span>' : '<span class="warning">⚠️</span>') . ' Database configured</p>';
                echo '<p>' . ($appDebug ? '<span class="warning">⚠️</span>' : '<span class="success">✅</span>') . ' Debug mode: ' . ($appDebug ? 'ON' : 'OFF') . '</p>';
            } else {
                echo '<p class="error">❌ .env file missing</p>';
            }
            ?>
        </div>

        <div class="section">
            <h2>🚀 Laravel Loading Test</h2>
            <?php
            echo '<p>Testing Laravel components...</p>';
            
            try {
                // Test 1: Autoloader
                echo '<p>1. Loading autoloader... ';
                require_once __DIR__ . '/vendor/autoload.php';
                echo '<span class="success">✅ SUCCESS</span></p>';
                
                // Test 2: Bootstrap
                echo '<p>2. Loading Laravel bootstrap... ';
                $app = require_once __DIR__ . '/bootstrap/app.php';
                echo '<span class="success">✅ SUCCESS</span></p>';
                
                // Test 3: Kernel
                echo '<p>3. Creating HTTP Kernel... ';
                $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
                echo '<span class="success">✅ SUCCESS</span></p>';
                
                // Test 4: Request handling
                echo '<p>4. Testing request handling... ';
                $request = Illuminate\Http\Request::create('/test', 'GET');
                echo '<span class="success">✅ SUCCESS</span></p>';
                
                echo '<div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;">';
                echo '<strong>🎉 Laravel is working correctly!</strong><br>';
                echo 'The 500 error might be caused by a specific route or configuration issue.';
                echo '</div>';
                
            } catch (Exception $e) {
                echo '<span class="error">❌ ERROR</span></p>';
                echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;">';
                echo '<strong>Error Details:</strong><br>';
                echo '<strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '<br>';
                echo '<strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '<br>';
                echo '<strong>Line:</strong> ' . $e->getLine() . '<br>';
                echo '<strong>Stack Trace:</strong><br>';
                echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
                echo '</div>';
            }
            ?>
        </div>

        <div class="section">
            <h2>📝 Recommended Actions</h2>
            <ol>
                <li><strong>Fix missing directories:</strong> Create any missing storage subdirectories</li>
                <li><strong>Set permissions:</strong> 755 for directories, 644 for files</li>
                <li><strong>Check .env:</strong> Ensure APP_KEY is generated and database is configured</li>
                <li><strong>Enable debug:</strong> Set APP_DEBUG=true in .env temporarily</li>
                <li><strong>Check logs:</strong> Look in storage/logs/ for Laravel error logs</li>
            </ol>
        </div>

        <div class="section">
            <h2>🔧 Quick Fixes</h2>
            <p><strong>Create missing directories:</strong></p>
            <pre>mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache</pre>
            
            <p><strong>Set permissions:</strong></p>
            <pre>chmod -R 755 storage bootstrap/cache
chmod 644 .env index.php .htaccess</pre>
        </div>

        <p style="text-align: center; margin-top: 30px;">
            <strong>🗑️ Delete this debug.php file after fixing the issues!</strong>
        </p>
    </div>
</body>
</html>
