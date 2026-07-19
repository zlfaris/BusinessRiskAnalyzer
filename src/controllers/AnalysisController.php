<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Analysis;
use App\Helpers\Validator;
use App\Config\GeminiClient;
use App\Config\GroqClient;
use Exception;
use InvalidArgumentException;
use RuntimeException;

class AnalysisController
{
    private Analysis $analysisModel;
    private GeminiClient $geminiClient;

    public function __construct()
    {
        $this->analysisModel = new Analysis();
        $this->geminiClient = new GeminiClient();
    }

    private function requireAuth(): int
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized. Please login.']);
            exit;
        }
        return $_SESSION['user_id'];
    }

    public function runAnalysis(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $userId = $this->requireAuth();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            Validator::validateAnalysisInput($data);

            // Fetch AI analysis from Gemini
            $geminiResponse = $this->processBusinessData($data);

            // Save to database
            $analysisId = $this->analysisModel->create($userId, $data, $geminiResponse);

            http_response_code(201);
            echo json_encode([
                'message' => 'Analysis created successfully.',
                'analysis_id' => $analysisId,
                'gemini_result' => $geminiResponse
            ]);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $userId = $this->requireAuth();
        
        $analyses = $this->analysisModel->findAllByUserId($userId);
        
        // Decode JSON back into arrays for response
        $analyses = array_map(function ($analysis) {
            $analysis['gemini_raw_response'] = json_decode($analysis['gemini_raw_response'], true);
            return $analysis;
        }, $analyses);

        http_response_code(200);
        echo json_encode(['data' => $analyses]);
    }

    public function show(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $userId = $this->requireAuth();
        
        $analysis = $this->analysisModel->findByIdAndUserId($id, $userId);

        if (!$analysis) {
            http_response_code(404);
            echo json_encode(['error' => 'Analysis not found.']);
            return;
        }

        $analysis['gemini_raw_response'] = json_decode($analysis['gemini_raw_response'], true);
        
        http_response_code(200);
        echo json_encode(['data' => $analysis]);
    }

    public function update(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $userId = $this->requireAuth();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $existing = $this->analysisModel->findByIdAndUserId($id, $userId);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['error' => 'Analysis not found.']);
            return;
        }

        try {
            Validator::validateAnalysisInput($data);

            // Re-run AI analysis with updated data
            $geminiResponse = $this->processBusinessData($data);

            // Update database
            $this->analysisModel->update($id, $userId, $data, $geminiResponse);

            http_response_code(200);
            echo json_encode([
                'message'      => 'Analysis updated successfully.',
                'analysis_id'  => $id,
                'gemini_result' => $geminiResponse
            ]);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function delete(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $userId = $this->requireAuth();
        
        $existing = $this->analysisModel->findByIdAndUserId($id, $userId);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['error' => 'Analysis not found.']);
            return;
        }

        $success = $this->analysisModel->delete($id, $userId);
        
        if ($success) {
            http_response_code(200);
            echo json_encode(['message' => 'Analysis deleted successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete analysis.']);
        }
    }

    private function processBusinessData(array $businessData)
    {
        try {
            // PRIORITAS MUTLAK: Selalu gunakan Gemini terlebih dahulu (Kriteria 3)
            $gemini = new GeminiClient();
            return $gemini->analyzeRisk($businessData); 

        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            
            // DETEKSI LIMIT: Hanya jika Gemini membalas dengan 429 (Too Many Requests) atau 503/Quota (Kriteria 5)
            $isLimitError = strpos($errorMessage, 'HTTP 429') !== false;
            $isServerError = strpos($errorMessage, 'HTTP 503') !== false;
            $isQuotaError = strpos(strtolower($errorMessage), 'quota') !== false;

            if ($isLimitError || $isServerError || $isQuotaError) {
                // FALLBACK OTOMATIS: Pindah ke Groq sebagai cadangan (Kriteria 1 & 5)
                try {
                    $groq = new GroqClient();
                    return $groq->analyzeRisk($businessData);

                } catch (RuntimeException $groqError) {
                    // Jika kedua AI (Gemini & Groq) tumbang bersamaan
                    throw new RuntimeException("Sistem analisis sedang sibuk. Mohon coba lagi dalam beberapa menit.");
                }
            }

            // Jika error dari Gemini BUKAN karena limit (misal: validasi input gagal)
            throw new RuntimeException("Gagal memproses data: " . $errorMessage);
        }
    }
}
