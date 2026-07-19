<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class Analysis
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(int $userId, array $data, array $geminiResponse): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO analyses (user_id, business_name, category, target_pasar_utama, market_saturation, capex, opex, average_price, gemini_raw_response)
            VALUES (:user_id, :business_name, :category, :target_pasar_utama, :market_saturation, :capex, :opex, :average_price, :gemini_raw_response)
        ");

        $stmt->execute([
            ':user_id'             => $userId,
            ':business_name'       => $data['business_name'],
            ':category'            => $data['category'],
            ':target_pasar_utama'  => $data['target_pasar_utama'],
            ':market_saturation'   => $data['market_saturation'],
            ':capex'               => $data['capex'],
            ':opex'                => $data['opex'],
            ':average_price'       => $data['average_price'],
            ':gemini_raw_response' => json_encode($geminiResponse)
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findAllByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM analyses WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function findByIdAndUserId(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM analyses WHERE id = :id AND user_id = :user_id LIMIT 1");
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        $analysis = $stmt->fetch();
        return $analysis ?: null;
    }

    public function update(int $id, int $userId, array $data, array $geminiResponse): bool
    {
        $stmt = $this->db->prepare("
            UPDATE analyses
            SET business_name = :business_name,
                category = :category,
                target_pasar_utama = :target_pasar_utama,
                market_saturation = :market_saturation,
                capex = :capex,
                opex = :opex,
                average_price = :average_price,
                gemini_raw_response = :gemini_raw_response
            WHERE id = :id AND user_id = :user_id
        ");

        return $stmt->execute([
            ':business_name'       => $data['business_name'],
            ':category'            => $data['category'],
            ':target_pasar_utama'  => $data['target_pasar_utama'],
            ':market_saturation'   => $data['market_saturation'],
            ':capex'               => $data['capex'],
            ':opex'                => $data['opex'],
            ':average_price'       => $data['average_price'],
            ':gemini_raw_response' => json_encode($geminiResponse),
            ':id'                  => $id,
            ':user_id'             => $userId
        ]);
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM analyses WHERE id = :id AND user_id = :user_id");
        return $stmt->execute([':id' => $id, ':user_id' => $userId]);
    }
}
