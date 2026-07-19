<?php

declare(strict_types=1);

namespace App\Helpers;

use InvalidArgumentException;

class Validator
{
    public static function validateAuth(string $email, string $password): void
    {
        if (!preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email)) {
            throw new InvalidArgumentException("Format email tidak valid.");
        }

        if (strlen($password) < 8) {
            throw new InvalidArgumentException("Password minimal harus 8 karakter.");
        }
    }

    public static function validateAnalysisInput(array $data): void
    {
        if (!isset($data['business_name']) || strlen(trim($data['business_name'])) < 5) {
            throw new InvalidArgumentException("Nama bisnis wajib diisi dan minimal 5 karakter.");
        }

        $validCategories = ['F&B', 'Jasa', 'Retail'];
        if (!isset($data['category']) || !in_array($data['category'], $validCategories, true)) {
            throw new InvalidArgumentException("Kategori tidak valid. Pilih salah satu dari: " . implode(', ', $validCategories));
        }

        if (!isset($data['target_pasar_utama']) || strlen(trim($data['target_pasar_utama'])) < 3) {
            throw new InvalidArgumentException("Target Pasar Utama wajib diisi (minimal 3 karakter).");
        }

        $validSaturations = ['Belum ada', 'Sedikit', 'Banyak', 'Sangat Jenuh'];
        if (!isset($data['market_saturation']) || !in_array($data['market_saturation'], $validSaturations, true)) {
            throw new InvalidArgumentException("Tingkat kejenuhan pasar tidak valid. Pilih: " . implode(', ', $validSaturations));
        }

        if (!isset($data['capex']) || !is_numeric($data['capex']) || (float)$data['capex'] < 0) {
            throw new InvalidArgumentException("CAPEX harus berupa angka positif.");
        }

        if (!isset($data['opex']) || !is_numeric($data['opex']) || (float)$data['opex'] < 0) {
            throw new InvalidArgumentException("OPEX harus berupa angka positif.");
        }

        if (!isset($data['average_price']) || !is_numeric($data['average_price']) || (float)$data['average_price'] <= 0) {
            throw new InvalidArgumentException("Harga rata-rata harus berupa angka positif lebih dari nol.");
        }
    }
}
