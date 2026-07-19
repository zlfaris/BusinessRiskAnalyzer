<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(string $name, string $email, string $passwordHash): int
    {
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :hash)");
        $stmt->execute([
            ':name'  => $name,
            ':email' => $email,
            ':hash'  => $passwordHash
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        
        $user = $stmt->fetch();
        return $user ?: null;
    }
}
