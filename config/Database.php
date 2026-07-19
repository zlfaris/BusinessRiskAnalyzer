<?php

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
        // Private constructor to prevent direct instantiation
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $dbName = getenv('DB_NAME') ?: 'business_risk_analyzer';
            $username = getenv('DB_USER') ?: 'root';
            $password = getenv('DB_PASS') ?: '';
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$dbName;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // Strict typing for prepared statements
            ];

            try {
                self::$instance = new PDO($dsn, $username, $password, $options);
                
                // Auto-migrate tables if missing (specifically for shared hosting like InfinityFree)
                $stmt = self::$instance->query("SHOW TABLES LIKE 'users'");
                if ($stmt->rowCount() == 0) {
                    $schemaPath = __DIR__ . '/../database/schema.sql';
                    if (file_exists($schemaPath)) {
                        $sql = file_get_contents($schemaPath);
                        // Remove CREATE DATABASE and USE statements which fail on shared hosting
                        $sql = preg_replace('/CREATE DATABASE[^;]+;/i', '', $sql);
                        $sql = preg_replace('/USE [^;]+;/i', '', $sql);
                        self::$instance->exec($sql);
                    }
                }
            } catch (PDOException $e) {
                // In a production app, log this error instead of displaying it.
                http_response_code(500);
                echo json_encode(['error' => 'Database connection failed.', 'message' => $e->getMessage()]);
                exit;
            }
        }

        return self::$instance;
    }
}
