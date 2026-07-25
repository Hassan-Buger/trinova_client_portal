<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<div style='font-family:sans-serif;padding:20px;max-width:800px;margin:0 auto;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;'>";
echo "<h1 style='color:#0f172a;margin-top:0;'>TriNova Path Diagnostics</h1>";
echo "<p><b>Current Directory (<code>__DIR__</code>):</b> <code>" . __DIR__ . "</code></p>";
echo "<p><b>Parent Directory (<code>dirname(__DIR__)</code>):</b> <code>" . dirname(__DIR__) . "</code></p>";

$trinova_app_path = dirname(__DIR__) . '/trinova_app';
echo "<p><b>Expected <code>trinova_app</code> Path:</b> <code>" . $trinova_app_path . "</code></p>";

if (is_dir($trinova_app_path)) {
    echo "<p style='color:green;font-weight:bold;'>✔ trinova_app directory exists!</p>";
    
    $autoload = $trinova_app_path . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        echo "<p style='color:green;font-weight:bold;'>✔ vendor/autoload.php exists!</p>";
    } else {
        echo "<p style='color:red;font-weight:bold;'>✘ vendor/autoload.php NOT found in trinova_app!</p><p><i>Tip: Make sure you ran <code>composer install</code> locally and zipped/uploaded the <code>vendor</code> folder, or that it is present in <code>trinova_app/vendor/</code>.</i></p>";
    }
    
    $env = $trinova_app_path . '/.env';
    if (file_exists($env)) {
        echo "<p style='color:green;font-weight:bold;'>✔ .env file exists in trinova_app!</p>";
        // Parse database configurations from .env securely
        $lines = file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $env_vars = [];
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            if (str_contains($line, '=')) {
                [$key, $val] = explode('=', $line, 2);
                $env_vars[trim($key)] = trim(trim($val), '"\'');
            }
        }
        echo "<div style='background:#f1f5f9;padding:12px;border-radius:6px;font-size:13px;'>";
        echo "<b>Loaded environment variables:</b><br>";
        echo "APP_ENV: " . htmlspecialchars($env_vars['APP_ENV'] ?? 'NOT SET') . "<br>";
        echo "APP_DEBUG: " . htmlspecialchars($env_vars['APP_DEBUG'] ?? 'NOT SET') . "<br>";
        echo "DB_HOST: " . htmlspecialchars($env_vars['DB_HOST'] ?? 'NOT SET') . "<br>";
        echo "DB_NAME: " . htmlspecialchars($env_vars['DB_NAME'] ?? 'NOT SET') . "<br>";
        echo "DB_USER: " . htmlspecialchars($env_vars['DB_USER'] ?? 'NOT SET') . "<br>";
        echo "STORAGE_DIR: " . htmlspecialchars($env_vars['STORAGE_DIR'] ?? 'NOT SET') . "<br>";
        echo "</div>";

        // Try DB connection
        if (isset($env_vars['DB_HOST'])) {
            try {
                $dsn = "mysql:host=" . $env_vars['DB_HOST'] . ";port=" . ($env_vars['DB_PORT'] ?? '3306') . ";dbname=" . $env_vars['DB_NAME'] . ";charset=utf8mb4";
                $pdo = new PDO($dsn, $env_vars['DB_USER'], $env_vars['DB_PASS']);
                echo "<p style='color:green;font-weight:bold;'>✔ Database connection successful!</p>";
            } catch (PDOException $e) {
                echo "<p style='color:red;font-weight:bold;'>✘ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    } else {
        echo "<p style='color:red;font-weight:bold;'>✘ .env file NOT found in trinova_app!</p>";
    }
} else {
    echo "<p style='color:red;font-weight:bold;'>✘ trinova_app directory does NOT exist at that path!</p>";
    echo "<p><b>Contents of parent directory (<code>" . dirname(__DIR__) . "</code>):</b></p>";
    $files = scandir(dirname(__DIR__));
    echo "<ul style='background:#f1f5f9;padding:15px 30px;border-radius:6px;'>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "<li><code>$file</code> (" . (is_dir(dirname(__DIR__) . '/' . $file) ? 'dir' : 'file') . ")</li>";
        }
    }
    echo "</ul>";
}
echo "</div>";
