<?php
/**
 * Laravel Application Key Generator
 * Use this to generate a secure APP_KEY for your .env file
 * 
 * Instructions:
 * 1. Upload this file to your domain root
 * 2. Visit: https://yourdomain.com/generate-key.php
 * 3. Copy the generated key to your .env file
 * 4. Delete this file after use for security
 */

// Generate a secure random key
function generateAppKey() {
    return 'base64:' . base64_encode(random_bytes(32));
}

// Simple security check
$allowed = true;
if (file_exists('.env')) {
    $envContent = file_get_contents('.env');
    if (strpos($envContent, 'APP_KEY=base64:') !== false && strpos($envContent, 'APP_KEY=base64:YOUR_GENERATED_KEY_HERE') === false) {
        $allowed = false;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adintel - Laravel Key Generator</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f8fafc;
        }
        .container {
            background: white;
            padding: 40px;
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
        .key-box {
            background: #f1f5f9;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            word-break: break-all;
            font-size: 14px;
        }
        .btn {
            background: #4f46e5;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #4338ca;
        }
        .btn-copy {
            background: #059669;
        }
        .btn-copy:hover {
            background: #047857;
        }
        .warning {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            color: #92400e;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .success {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .error {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .steps {
            background: #f8fafc;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .step {
            margin: 10px 0;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .step:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🚀 Adintel</div>
            <h1>Laravel Application Key Generator</h1>
            <p>Generate a secure encryption key for your Adintel platform</p>
        </div>

        <?php if (!$allowed): ?>
            <div class="error">
                <strong>⚠️ Security Notice:</strong> It appears you already have an APP_KEY configured. 
                For security reasons, this generator is disabled. If you need to regenerate your key, 
                please remove the existing APP_KEY from your .env file first.
            </div>
        <?php else: ?>
            <?php
            $newKey = generateAppKey();
            ?>
            
            <div class="success">
                <strong>✅ Key Generated Successfully!</strong> Your new Laravel application key is ready to use.
            </div>

            <h3>Your Generated APP_KEY:</h3>
            <div class="key-box" id="keyBox">
                APP_KEY=<?php echo htmlspecialchars($newKey); ?>
            </div>

            <div style="text-align: center;">
                <button class="btn btn-copy" onclick="copyKey()">📋 Copy Key</button>
                <button class="btn" onclick="generateNew()">🔄 Generate New Key</button>
            </div>

            <div class="steps">
                <h3>📋 Next Steps:</h3>
                <div class="step">
                    <strong>1.</strong> Copy the generated key above
                </div>
                <div class="step">
                    <strong>2.</strong> Open your <code>.env</code> file
                </div>
                <div class="step">
                    <strong>3.</strong> Replace the APP_KEY line with the generated key
                </div>
                <div class="step">
                    <strong>4.</strong> Save the <code>.env</code> file
                </div>
                <div class="step">
                    <strong>5.</strong> <strong style="color: #dc2626;">Delete this file (generate-key.php) for security!</strong>
                </div>
            </div>

            <div class="warning">
                <strong>🔒 Security Important:</strong> 
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Keep your APP_KEY secret and secure</li>
                    <li>Never share it publicly or commit it to version control</li>
                    <li>Delete this generator file after use</li>
                    <li>Each installation should have a unique key</li>
                </ul>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <p><strong>🎉 Ready to launch your Adintel platform!</strong></p>
                <p>Visit your domain after updating the .env file to start using the platform.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function copyKey() {
            const keyText = document.getElementById('keyBox').textContent.trim();
            navigator.clipboard.writeText(keyText).then(function() {
                alert('✅ Key copied to clipboard!');
            }, function(err) {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = keyText;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('✅ Key copied to clipboard!');
            });
        }

        function generateNew() {
            if (confirm('Generate a new key? The current key will be replaced.')) {
                location.reload();
            }
        }

        // Auto-select key text when clicked
        document.getElementById('keyBox').addEventListener('click', function() {
            const range = document.createRange();
            range.selectNodeContents(this);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
        });
    </script>
</body>
</html>
