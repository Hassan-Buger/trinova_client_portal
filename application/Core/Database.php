<?php

namespace Application\Core;

use Application\Config\Database as DatabaseConfig;
use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = DatabaseConfig::getConfig();
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                $config['host'],
                $config['port'],
                $config['dbname'],
                $config['charset']
            );

            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                self::renderSetupPage($e->getMessage(), $config);
                exit;
            }
        }

        return self::$instance;
    }

    private static function renderSetupPage(string $errorMsg, array $config): void
    {
        http_response_code(500);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Database Setup Required — TriNova Portal</title>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
            <style>
                body { margin: 0; font-family: 'Plus Jakarta Sans', sans-serif; background: #eef4f1; color: #213330; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 24px; }
                .card { background: #fff; border-radius: 24px; max-width: 580px; width: 100%; padding: 36px; box-shadow: 0 14px 34px -24px rgba(16,54,45,.4); border: 1px solid rgba(20,60,50,.08); }
                .badge { display: inline-block; background: #fdecdc; color: #e07d24; font-weight: 700; font-size: 12px; padding: 5px 12px; border-radius: 999px; text-transform: uppercase; margin-bottom: 16px; }
                h1 { margin: 0 0 10px; font-size: 24px; font-weight: 800; }
                p { margin: 0 0 20px; color: #61756e; font-size: 14.5px; line-height: 1.5; }
                .error-box { background: #fff8ee; border: 1.5px solid #f6dfc0; border-radius: 16px; padding: 14px 18px; font-family: monospace; font-size: 13px; color: #a85a0d; margin-bottom: 24px; word-break: break-all; }
                .steps { background: #f8faf9; border-radius: 18px; padding: 20px; font-size: 14px; line-height: 1.6; border: 1px solid #e0e9e5; }
                .steps ol { margin: 0; padding-left: 20px; }
                .steps code { background: #e6f0ed; color: #0f766e; padding: 2px 6px; border-radius: 6px; font-family: monospace; font-size: 12.5px; }
            </style>
        </head>
        <body>
            <div class="card">
                <span class="badge">MySQL Database Connection Error</span>
                <h1>Database Setup Required</h1>
                <p>The TriNova Client Portal application cannot connect to the MySQL database server using the configured credentials.</p>
                
                <div class="error-box">
                    <strong>Error Message:</strong><br>
                    <?= htmlspecialchars($errorMsg) ?>
                </div>

                <div class="steps">
                    <strong>How to Fix This Issue:</strong>
                    <ol>
                        <li><strong>Local Setup (XAMPP / Wamp / MySQL)</strong>:<br>
                            Ensure MySQL is running on host <code><?= htmlspecialchars($config['host']) ?></code> port <code><?= htmlspecialchars($config['port']) ?></code>, and import <code>config/database.sql</code> into your database tool.
                        </li>
                        <li style="margin-top: 10px;"><strong>Hostinger Hosting Deployment</strong>:<br>
                            Create a MySQL database in Hostinger hPanel, then create a <code>.env</code> file in your project root with your Hostinger database details:
                            <pre style="background:#fff;padding:10px;border-radius:10px;margin-top:6px;font-size:12px">DB_HOST=localhost
DB_NAME=your_hostinger_db_name
DB_USER=your_hostinger_db_user
DB_PASS=your_hostinger_db_password</pre>
                        </li>
                    </ol>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
}
