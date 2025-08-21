<?php
/**
 * Adintel Platform Diagnostic Script
 * Use this to diagnose hosting issues
 * 
 * Upload this file to your domain root and visit:
 * https://adintel.redbananas.com/diagnostic.php
 */

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adintel - Hosting Diagnostic</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #f8fafc;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 2em;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 10px;
        }
        .check {
            margin: 15px 0;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #10b981;
        }
        .check.error {
            border-left-color: #ef4444;
            background: #fee2e2;
        }
        .check.warning {
            border-left-color: #f59e0b;
            background: #fef3c7;
        }
        .check.success {
            border-left-color: #10b981;
            background: #d1fae5;
        }
        .code {
            background: #f1f5f9;
            padding: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            margin: 10px 0;
        }
        .section {
            margin: 30px 0;
            padding: 20px;
            background: #f8fafc;
            border-radius: 6px;
        }
        h3 {
            color: #374151;
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🔍 Adintel Diagnostic</div>
            <h1>Hosting Environment Check</h1>
            <p>Diagnosing issues with your Adintel platform deployment</p>
        </div>

        <div class="section">
            <h3>📋 System Information</h3>
            
            <div class="check success">
                <strong>✅ PHP Version:</strong> <?php echo PHP_VERSION; ?>
                <?php if (version_compare(PHP_VERSION, '8.2.0', '>=')): ?>
                    <span style="color: #059669;">(Compatible)</span>
                <?php else: ?>
                    <span style="color: #dc2626;">(Requires PHP 8.2+)</span>
                <?php endif; ?>
            </div>

            <div class="check success">
                <strong>✅ Current Directory:</strong> <?php echo __DIR__; ?>
            </div>

            <div class="check success">
                <strong>✅ Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>
            </div>

            <div class="check success">
                <strong>✅ Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?>
            </div>
        </div>

        <div class="section">
            <h3>📁 File Structure Check</h3>
            
            <?php
            $requiredFiles = [
                'index.php' => 'Laravel entry point',
                '.htaccess' => 'URL rewriting configuration',
                'vendor/autoload.php' => 'Composer autoloader',
                'bootstrap/app.php' => 'Laravel bootstrap',
                '.env' => 'Environment configuration',
                'artisan' => 'Laravel command line tool',
                'app/' => 'Application directory',
                'config/' => 'Configuration directory',
                'database/' => 'Database directory',
                'resources/' => 'Resources directory',
                'routes/' => 'Routes directory',
                'storage/' => 'Storage directory'
            ];

            foreach ($requiredFiles as $file => $description) {
                $exists = file_exists(__DIR__ . '/' . $file);
                $class = $exists ? 'success' : 'error';
                $icon = $exists ? '✅' : '❌';
                echo "<div class='check $class'>";
                echo "<strong>$icon $file:</strong> $description ";
                echo $exists ? '<span style="color: #059669;">(Found)</span>' : '<span style="color: #dc2626;">(Missing)</span>';
                echo "</div>";
            }
            ?>
        </div>

        <div class="section">
            <h3>🔧 PHP Extensions Check</h3>
            
            <?php
            $requiredExtensions = [
                'pdo' => 'Database connectivity',
                'pdo_mysql' => 'MySQL database support',
                'mbstring' => 'Multibyte string support',
                'openssl' => 'SSL/TLS support',
                'tokenizer' => 'PHP tokenizer',
                'xml' => 'XML processing',
                'ctype' => 'Character type checking',
                'json' => 'JSON support',
                'bcmath' => 'Arbitrary precision mathematics',
                'fileinfo' => 'File information',
                'curl' => 'HTTP client support'
            ];

            foreach ($requiredExtensions as $ext => $description) {
                $loaded = extension_loaded($ext);
                $class = $loaded ? 'success' : 'error';
                $icon = $loaded ? '✅' : '❌';
                echo "<div class='check $class'>";
                echo "<strong>$icon $ext:</strong> $description ";
                echo $loaded ? '<span style="color: #059669;">(Loaded)</span>' : '<span style="color: #dc2626;">(Missing)</span>';
                echo "</div>";
            }
            ?>
        </div>

        <div class="section">
            <h3>📂 Directory Contents</h3>
            <div class="code">
                <?php
                $files = scandir(__DIR__);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $type = is_dir(__DIR__ . '/' . $file) ? '[DIR]' : '[FILE]';
                        $size = is_file(__DIR__ . '/' . $file) ? ' (' . filesize(__DIR__ . '/' . $file) . ' bytes)' : '';
                        echo htmlspecialchars("$type $file$size") . "\n";
                    }
                }
                ?>
            </div>
        </div>

        <div class="section">
            <h3>🔍 Environment Check</h3>
            
            <?php if (file_exists(__DIR__ . '/.env')): ?>
                <div class="check success">
                    <strong>✅ .env file:</strong> Found
                </div>
                <?php
                $envContent = file_get_contents(__DIR__ . '/.env');
                $hasAppKey = strpos($envContent, 'APP_KEY=base64:') !== false && strpos($envContent, 'YOUR_GENERATED_KEY_HERE') === false;
                $hasDbConfig = strpos($envContent, 'DB_DATABASE=') !== false;
                ?>
                
                <div class="check <?php echo $hasAppKey ? 'success' : 'error'; ?>">
                    <strong><?php echo $hasAppKey ? '✅' : '❌'; ?> APP_KEY:</strong> 
                    <?php echo $hasAppKey ? 'Configured' : 'Not configured - use generate-key.php'; ?>
                </div>
                
                <div class="check <?php echo $hasDbConfig ? 'success' : 'warning'; ?>">
                    <strong><?php echo $hasDbConfig ? '✅' : '⚠️'; ?> Database:</strong> 
                    <?php echo $hasDbConfig ? 'Configured' : 'Needs configuration'; ?>
                </div>
            <?php else: ?>
                <div class="check error">
                    <strong>❌ .env file:</strong> Missing - rename .env.production to .env
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>🚀 Quick Fixes</h3>
            
            <?php if (!file_exists(__DIR__ . '/index.php')): ?>
                <div class="check error">
                    <strong>❌ Missing index.php</strong><br>
                    Create index.php in your root directory with Laravel bootstrap code.
                </div>
            <?php endif; ?>
            
            <?php if (!file_exists(__DIR__ . '/.htaccess')): ?>
                <div class="check error">
                    <strong>❌ Missing .htaccess</strong><br>
                    Create .htaccess file to handle URL rewriting and disable directory listing.
                </div>
            <?php endif; ?>
            
            <?php if (!file_exists(__DIR__ . '/.env')): ?>
                <div class="check error">
                    <strong>❌ Missing .env</strong><br>
                    Rename .env.production to .env and configure your database settings.
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>📞 Next Steps</h3>
            <ol>
                <li><strong>Fix missing files</strong> identified above</li>
                <li><strong>Set file permissions:</strong> 755 for directories, 644 for files</li>
                <li><strong>Configure .env</strong> with your database credentials</li>
                <li><strong>Import database</strong> using database_export.sql</li>
                <li><strong>Generate APP_KEY</strong> using generate-key.php</li>
                <li><strong>Test the application</strong> by visiting your domain</li>
            </ol>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <p><strong>🔧 Delete this diagnostic.php file after fixing the issues!</strong></p>
        </div>
    </div>
</body>
</html>
